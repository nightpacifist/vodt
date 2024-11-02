<?php

namespace app\controllers;

use app\services\Authentication;
use app\services\Config;
use app\services\Telegram;

class TelegramController extends \app\services\base\Controller
{
    public function callback(){




        //var_dump('awdaw');
        $data = json_decode(file_get_contents("php://input"), true);
        file_put_contents("test.txt", file_get_contents("php://input"), FILE_APPEND);

        (new Telegram())->run($data);
        //return $this->renderJson(['status' => true]);
    }

    public function authRedirect($id, $first_name, $username, $photo_url, $auth_date, $hash){


        $check_hash = $hash;

        $data_check_arr = [];

        $data_check_arr[] = 'id=' . $id;
        $data_check_arr[] = 'first_name=' . $first_name;
        $data_check_arr[] = 'username=' . $username;
        $data_check_arr[] = 'photo_url=' . $photo_url;
        $data_check_arr[] = 'auth_date=' . $auth_date;

        sort($data_check_arr);

        $data_check_string = implode("\n", $data_check_arr);

        $secret_key = hash('sha256', Config::get('bot_token'), true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);

        if (strcmp($hash, $check_hash) !== 0) {
            throw new \Exception('Data is NOT from Telegram');
        }

        if ((time() - $auth_date) > 86400) {
            throw new \Exception('Data is outdated');
        }

        $auth = new Authentication();

        if($auth->isAuth()){
            header("Location: /dashbord");
            exit;
        }

        $customer_id = $auth->saveUser($first_name, $username, $id);

        $telegram = new Telegram();

        $telegram->chat_id = $id;

        $telegram->sendWelcomeMessages();
        $telegram->sendFlowers();

        $hash = $auth->createToken($customer_id);
        setcookie('tg_user', $hash, time()+3600);
        header("Location: /dashbord");
        exit;
    }

}