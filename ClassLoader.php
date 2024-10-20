<?php

namespace app;

class ClassLoader
{
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new ClassLoader();
        }

        spl_autoload_register([self::$instance, "load"]);
    }

    public function load($name) {

        if(stripos("app", $name) == 0){
            $name = substr($name, 4, strlen($name));
        }

        include_once($_SERVER["DOCUMENT_ROOT"] . "/" . str_replace("\\", "/", $name) . ".php");
    }

}