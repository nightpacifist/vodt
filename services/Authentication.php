<?php

namespace app\services;

use app\services\base\DataBase;


class Authentication
{


    public function saveUser($first_name, $last_name = '', $phone = '', $email = ''){
        $data_base = DataBase::getInstance();

        $data_base->setTable('customers');

        $customer = $data_base->where(['last_name' => $last_name])->one();

        if(!is_null($customer)){
            return $customer['id'];
        }

        $customer_id = $data_base->insert([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email,
        ]);
        return $customer_id;
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
            'expires_at' => 0,
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