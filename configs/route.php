<?php

use app\services\Route;

Route::get('/test/{test:([0-9]+)}', 'app\\controllers\\TestController:test');