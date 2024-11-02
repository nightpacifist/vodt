<?php

namespace app\services;

use app\services\base\DataBase;


class Authentication
{

    public function checkCustomer($chat_id){
        $data_base = DataBase::getInstance();

        $data_base->setTable('customers');

        $customer = $data_base->where(['username' => $chat_id])->one();

        if(is_null($customer)){
            return false;
        }

        return true;

    }

    public function saveUser($first_name, $username, $chat_id, $phone = '', $email = ''){
        $data_base = DataBase::getInstance();

        $data_base->setTable('customers');

        $customer = $data_base->where(['username' => $username])->one();

        if(!is_null($customer)){
            return $customer['id'];
        }

        return $data_base->insert([
            'first_name' => $first_name,
            'username' => $username,
            'phone' => $phone,
            'email' => $email,
            'chat_id' => $chat_id
        ]);
    }

    public function createToken($customer_id){

        $data_base = DataBase::getInstance();

        $data_base->setTable('tokens');

        $rand = rand(0, 1000);

        $hash = hash('sha256', Config::get('secret'). $customer_id . time() . $customer_id . $rand . Config::get('secret'));

        $data_base->delete([
            'customer_id' => $customer_id
        ]);

        $data_base->insert([
            'customer_id' => $customer_id,
            'hash' => $hash,
            'expires_at' => time()+3600,
        ]);

        return $hash;

    }

    public function isAuth(){

        if(!isset($_COOKIE['tg_user'])){
            return false;
        }

        $data_base = DataBase::getInstance();

        $data_base->setTable('tokens');

        $auth = $data_base->where(['hash' => $_COOKIE['tg_user']])->one();

        if(!is_null($auth)){
            return true;
        }

        return false;

    }
}