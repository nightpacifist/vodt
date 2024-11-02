<?php

namespace app\services;

use app\services\base\DataBase;

class Mono
{

    public function createTransaction($order_id){
        $data_base = DataBase::getInstance();

        $data_base->setTable('order_products');

        $order = $data_base->where(['order_id' => $order_id])->all();

        $price = 0;

        foreach ($order as $item) {
            $price += floatval($item['price_per_one']) * intval($item['quantity']);
        }

        $price = intval($price*100);

        return json_decode($this->send([
            'amount' => $price,
            'redirectUrl' => 'https://vodt.domain-for-tests.com/mono/redirect?order_id=' . $order_id,
            'webHookUrl' => 'https://vodt.domain-for-tests.com/mono/callback?order_id=' . $order_id
        ]), true);
    }

    public function send($post_data, $headers = [])
    {
        $token = Config::get('mono_token'); // Замініть на ваш токен
        $url = "https://api.monobank.ua/api/merchant/invoice/create";

        $headers[] = 'X-Token: ' . $token;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        curl_close($ch);
        return $body;
    }

    public function confirmTransaction($order_id){
        $data_base = DataBase::getInstance();

        $data_base->setTable('orders');

        $order = $data_base->where(['id' => $order_id, 'status' => 0])->one();
        if(!is_null($order)){
            $data_base->update([
                'status' => 1
            ],
            [
                'id' => $order['id']
            ]);
        }
    }

}