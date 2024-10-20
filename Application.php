<?php

namespace app;

use app\services\Route;

class Application
{
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new Application();
        }

        return self::$instance;
    }

    public function init() {

//        var_dump($_SERVER['REQUEST_URI']);
//        if(!isset($_GET["route"])) {
//            exit();
//        }

        Route::run($_SERVER['REQUEST_URI'], 'get');

//        $route = $_GET["route"];
//        $data = explode("/", $route);
//
//        $class = "\\Controller\\" . $data[0];
//
//        $controller = new $class();
//        echo $controller->{$data[1]}();
    }
}