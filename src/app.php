<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/cloud.php';
require __DIR__ . '/../src/api.php';

/*
 * A simple Slim based sample application
 *
 * See Slim documentation:
 * http://www.slimframework.com/docs/
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Views\PhpRenderer;
use \LeanCloud\LeanClient;
use \LeanCloud\Storage\CookieStorage;
use \LeanCloud\Engine\SlimEngine;
use \LeanCloud\LeanQuery;
use \LeanCloud\LeanObject;

$app = new \Slim\App();
// 禁用 Slim 默认的 handler，使得错误栈被日志捕捉
unset($app->getContainer()['errorHandler']);

LeanClient::initialize(
    getenv("LC_APP_ID"),
    getenv("LC_APP_KEY"),
    getenv("LC_APP_MASTER_KEY")
);
// 将 sessionToken 持久化到 cookie 中，以支持多实例共享会话
LeanClient::setStorage(new CookieStorage());

SlimEngine::enableHttpsRedirect();
$app->add(new SlimEngine());

// 使用 Slim/PHP-View 作为模版引擎
$container = $app->getContainer();
$container["view"] = function ($container) {
    return new \Slim\Views\PhpRenderer(__DIR__ . "/views/");
};

$app->get('/', function (Request $request, Response $response) {
    return $this->view->render($response, "index.phtml", array(
        "currentTime" => new \DateTime(),
    ));
});

// 显示 todo 列表
$app->get('/todos', function (Request $request, Response $response) {
    $query = new LeanQuery("Todo");
    $query->descend("createdAt");
    try {
        $todos = $query->find();
    } catch (\Exception $ex) {
        error_log("Query todo failed!");
        $todos = array();
    }
    return $this->view->render($response, "todos.phtml", array(
        "title" => "TODO 列表",
        "todos" => $todos,
    ));
});

$app->post("/todos", function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $todo = new LeanObject("Todo");
    $todo->set("content", $data["content"]);
    $todo->save();
    return $response->withStatus(302)->withHeader("Location", "/todos");
});

$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");

    return $response;
});

$app->get('/quanmin/{roomid}', function (Request $request, Response $response) {
    $roomid = $request->getAttribute('roomid');
    $query = new LeanQuery("Quanmin");
    $query->descend("createdAt");
    try {
        $todos = $query->find();
    } catch (\Exception $ex) {
        error_log("Query todo failed!");
        $todos = array();
    }
    foreach ($todos as $m) {
        if ($m->get('roomid') == $roomid) {
            $result = getQuanminData("http://www.quanmin.tv/json/rooms/" . $roomid . "/info.json");
        }
    }
    return $result;
});
$app->get('/douyu', function (Request $request, Response $response) {
    return getDouyuData();
});
$app->get('/huya', function (Request $request, Response $response) {
    $result = getHuyaData();
    return $result;
});
$app->get('/zhanqi/{roomid}', function (Request $request, Response $response) {
    $roomid = $request->getAttribute('roomid');
    $query = new LeanQuery("Zhanqi");
    $query->descend("createdAt");
    try {
        $todos = $query->find();
    } catch (\Exception $ex) {
        error_log("Query todo failed!");
        $todos = array();
    }
    foreach ($todos as $m) {
        if ($m->get('roomid') == $roomid) {
            $result = getQuanminData("http://www.zhanqi.tv/api/static/live.roomid/" . $roomid . ".json");
        }
    }
    return $result;
});
$app->run();

