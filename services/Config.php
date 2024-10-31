<?php

namespace app\services;

class Config
{
    private static array $configs = [];

    public static function get($name){
        return Config::$configs[$name]??false;
    }

    public static function set($name, $value){
        Config::$configs[$name] = $value;
    }

}