<?php

namespace app\services\base;

abstract class Controller
{

    public function render($view, $data){
        extract($data);
        require($_SERVER["DOCUMENT_ROOT"] . '/views/' . $view . '.php');
        ob_flush();
        return true;
    }

    public function renderJson($data){
        $json = json_encode($data);
        $json_len = strlen($json);

        header("Content-Type: application/json");
        header('Content-Length: ' . $json_len);

        echo $json;
        return true;
    }

}