<?php

use app\services\Config;

Config::set('db_host', getenv('db_host'));
Config::set('db_user', getenv('db_user'));
Config::set('db_password', getenv('db_password'));
Config::set('db_name', getenv('db_name'));
Config::set('bot_token', getenv('bot_token'));