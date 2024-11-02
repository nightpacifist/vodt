<?php

use app\services\Route;

Route::get('/', 'app\\controllers\\HomeController:index');
Route::post('/telegram/callback', 'app\\controllers\\TelegramController:callback');
Route::get('/auth', 'app\\controllers\\TelegramController:authRedirect');
Route::get('/dashbord', 'app\\controllers\\HomeController:dashbord');


Route::post('/mono/callback', 'app\\controllers\\MonoController:callback');
Route::post('/mono/redirect', 'app\\controllers\\MonoController:redirect');

//test/{test:([0-9]+)}