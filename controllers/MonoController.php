<?php

namespace app\controllers;

use app\services\Mono;

class MonoController
{
    public function callback($order_id){
        $pubKeyBase64 = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUZrd0V3WUhLb1pJemowQ0FRWUlLb1pJemowREFRY0RRZ0FFc05mWXpNR1hIM2VXVHkzWnFuVzVrM3luVG5CYgpnc3pXWnhkOStObEtveDUzbUZEVTJONmU0RlBaWmsvQmhqamgwdTljZjVFL3JQaU1EQnJpajJFR1h3PT0KLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==';
        $headers = getallheaders();

        $xSignBase64 = $headers['X-Sign'];

        $message = file_get_contents("php://input");

        file_put_contents("test.txt", json_encode($headers), FILE_APPEND);
        file_put_contents("test.txt", $message, FILE_APPEND);

        $signature = base64_decode($xSignBase64);

        $publicKey = openssl_get_publickey(base64_decode($pubKeyBase64));

        $result = openssl_verify($message, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        $data = json_decode($message, true);

        if($result === 1 && $data['status'] == 'success'){
            $mono = new Mono();
            $mono->confirmTransaction($order_id);
        }

        echo $result === 1 ? "OK" : "NOT OK";
    }

    public function redirect(){
        header("Location: https://t.me/flowersuakh_bot?start=get_order");
        exit;

    }

}