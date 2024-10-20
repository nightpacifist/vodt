<?php
ini_set('display_startup_errors', 1); ini_set('display_errors', 1); error_reporting(E_ALL);
include_once("ClassLoader.php");

use app\ClassLoader;
use app\Application;


ClassLoader::getInstance();

include_once("configs/route.php");

Application::getInstance()->init();