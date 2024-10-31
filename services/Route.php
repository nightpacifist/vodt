<?php

namespace app\services;

class Route
{
    private static $post = [];
    private static $get = [];

    public static function get($uri, $action){
        Route::$get[] = ['uri' => $uri, 'action' => explode(":", $action)];
    }

    public static function post($uri, $action){
        Route::$post[] = ['uri' => $uri, 'action' => $action];
    }

    public static function run($uri, $method = 'get'){
        foreach (Route::$get as $item) {
            $route_rule = Route::getPattern($item['uri']);

            $uri = explode("?", $uri)[0];

            if(preg_match($route_rule['pattern'], $uri, $matches)){

                $get_var = [];
                foreach ($route_rule['data'] as $key => $name_get_var) {
                    $get_var[$name_get_var] = $matches[$key];
                }

                if(!empty($_GET)){
                    foreach ($_GET as $key => $value) {
                        $get_var[$key] = $value;
                    }
                }

                $class = $item['action'][0];
                $action = $item['action'][1];
                $controller = new $class();

                $reflection = new \ReflectionMethod($controller, $action);

                $params = $reflection->getParameters();

                $final_data = [];
                foreach ($params as $param) {
                    if ($param->isOptional()) {
                        if(isset($get_var[$param->getName()])){
                            $value = $get_var[$param->getName()];
                        }else{
                            if ($param->isDefaultValueAvailable()) {
                                $value = $param->getDefaultValue();
                            }
                        }
                    } else {
                        if(isset($get_var[$param->getName()])){
                            $value = $get_var[$param->getName()];
                        }else{
                            exit("Param: " . $param->getName() . " is not set!!!");
                        }
                    }

                    $final_data[] = $value;
                }

                call_user_func_array([$controller, $action], $final_data);
            }
        }
    }

    public static function getPattern($uri){
        $patterns = explode("/", $uri);

        //var_dump($uri);
        $data = [];
        $new_pattern = '';

        $i = 1;
        foreach ($patterns as $pattern) {
            if($pattern != '' && stripos($pattern, '{') !== false){
                $pattern = trim($pattern, "{}");
                $pattern = explode(":", $pattern);
                $data[$i] = $pattern[0];
                $new_pattern .= $pattern[1] . '/';
                $i++;
            }else{
                $new_pattern .= $pattern . '/';
            }
        }

        if($new_pattern != "//"){
            $new_pattern = rtrim($new_pattern, "/");
        }else{
            $new_pattern = '/';
        }



        //var_dump($data);


        return ['data' => $data, 'pattern' => '#^' . $new_pattern . '$#'];

    }

}