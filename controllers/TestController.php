<?php

namespace app\controllers;

use app\services\base\Controller;

class TestController extends Controller
{
    public function test($test){
        return $this->renderJson(['test' => $test]);
    }

}