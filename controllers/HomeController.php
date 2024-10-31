<?php

namespace app\controllers;

use app\services\Authentication;
use app\services\base\Controller;

class HomeController extends Controller
{
    public function index(){
        return $this->render('index');
    }

    public function dashbord(){
        $auth = new Authentication();
        if(!$auth->isAuth()){
            header("Location: /");
            exit;
        }
        return $this->render('dashbord');
    }

}