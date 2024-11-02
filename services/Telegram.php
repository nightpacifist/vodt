<?php

namespace app\services;

use app\services\base\DataBase;

class Telegram
{
    public $data;
    public $command = null;
    public $command_info = null;
    public $command_data = null;
    public $chat_id;

    public $available_command = [
        '/start',
        '/get_order',
        '/get_flowers',
        '/buy',
        '/get_transaction_link'
    ];

    public function run($data){
        $this->data = $data;

        if(!$this->getCommand()){
            return false;
        }

        switch ($this->command){
            case '/start':
                if(in_array('get_order', $this->command_info)){
                    $this->getInfoOrder();
                }else{
                    $this->sendWelcomeMessages();
                    $this->sendFlowers();
                }
                break;
            case '/get_flowers':
                $this->sendFlowers();
                break;
            case '/buy':
                $this->addProductToOrder();
                $this->getInfoOrder();
                break;
            case '/get_order':
                $this->getInfoOrder();
                break;
            case '/get_transaction_link':
                $this->getTransaction();
                break;

        }

        return false;
    }

    public function getCommand(){
        if(isset($this->data['callback_query'])){
            $this->data = $this->data['callback_query'];
            $this->command_data = $this->data['data']??'';
        }

        if(isset($this->data['message']['entities']) && $this->data['message']['entities'][0]['type'] == 'bot_command'){
            $text_data = explode(' ', $this->data['message']['text']);
            $this->command = array_shift($text_data);

            if(!in_array($this->command, $this->available_command)){
                return false;
            }

            $this->command_info = $text_data;
        }

        if(is_null($this->command_data) && is_null($this->command)){
            return false;
        }

        if(!is_null($this->command_data)){

            $info = explode(" ", $this->command_data);

            if(!in_array($info[0], $this->available_command)){
                return false;
            }

            $this->command = array_shift($info);
            $this->command_data = implode(" ", $info);


        }

        $this->chat_id = $this->data['message']['chat']['id'];

        $auth = new Authentication();

        if(!$auth->checkCustomer($this->chat_id)){
            $auth->saveUser($this->data['message']['chat']['first_name'], $this->data['message']['chat']['username'], $this->data['message']['chat']['id']);
        }

        return true;

    }

    public function sendFlowers()
    {
        $data_base = DataBase::getInstance();

        $data_base->setTable('products');
        $flowers = $data_base->all();


        $this->sendMessages([
            'chat_id' => $this->chat_id,
            'text' => 'Каталог актуальних квітів:'
        ]);

        foreach ($flowers as $flower) {

            $text = $flower['name'];

            // Відповідь з inline-кнопками
            $reply_markup = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Купити', 'callback_data' => '/buy ' . $flower['id']]
                    ]
                ]
            ];


            if(is_null($flower['file_id'])){
                $photo = 'https://vodt.domain-for-tests.com/images/' . $flower['img'];
            }else{
                $photo = $flower['file_id'];
            }

            $data = $this->sendPhoto([
                'chat_id' => $this->chat_id,
                'caption' => $text,
                'photo' => $photo,
                'reply_markup' => json_encode($reply_markup)
            ]);

            $data = json_decode($data, true);

            if(is_null($flower['file_id'])){
                $data_base->update([
                    'file_id' => $data['result']['photo'][0]['file_id']
                ],
                [
                    'id' => $flower['id']
                ]);
            }

        }


    }

    public function addProductToOrder(){
        $data_base = DataBase::getInstance();

        $data_base->setTable('customers');

        $customer = $data_base->where(['chat_id' => $this->chat_id])->one();

        $data_base->setTable('products');

        $product = $data_base->where(['id' => $this->command_data])->one();

        if(is_null($product)){
            return false;
        }

        $data_base->setTable('orders');

        $order = $data_base->where(['customer_id' => $customer['id'], 'status' => 0])->one();

        if(is_null($order)){
            $order_id = $data_base->insert([
                'status' => 0,
                'customer_id' => $customer['id']
            ]);
        }else{
            $order_id = $order['id'];
        }

        $data_base->setTable('order_products');

        $products_order = $data_base->where(['order_id' => $order_id, 'product_id' => $product['id']])->one();


        if(is_null($products_order)){
            $product_order_id = $data_base->insert([
                'order_id' => $order_id,
                'product_id' => $product['id'],
                'quantity' => 1,
                'price_per_one' => $product['price_per_one']
            ]);
        }else{
            $data_base->update([
                'quantity' => $products_order['quantity'] + 1
            ],
            [
                'id' => $products_order['id']
            ]);
        }

        return true;

    }

    public function getInfoOrder(){
        $data_base = DataBase::getInstance();

        $data_base->setTable('customers');

        $customer = $data_base->where(['chat_id' => $this->chat_id])->one();
        $data_base->setTable('orders');

        $orders = $data_base->where(['customer_id' => $customer['id']])->all();

        $this->sendMessages([
            'chat_id' => $this->chat_id,
            'text' => 'Ваші замовлення: '
        ]);

        foreach ($orders as $order) {

            $data_base->setTable('order_products');

            $products_order = $data_base->where(['order_id' => $order['id']])->all();

            if($order['status'] == 0){
                $status = 'Нове замовлення';
            }else{
                $status = 'Замовлення оплачене';
            }

            $text = 'Замовлення #' . $order['id'] . "\n";
            $text .= 'Статус: ' . $status . "\n";
            $text .= 'Продукти: ' . "\n";

            $order_price = 0;

            foreach ($products_order as $item) {
                $data_base->setTable('products');
                $product = $data_base->where(['id' => $item['product_id']])->one();
                $text .= $product['name'] . ', кількість: ' . $item['quantity'] . '. Ціна: ' . (floatval($item['price_per_one']) * intval($item['quantity'])) . "\n";
                $order_price += floatval($item['price_per_one']) * intval($item['quantity']);
            }

            $text .= 'Загальна ціна замовлення: ' . $order_price;

            if($order['status'] == 0){
                $reply_markup = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Оформити', 'callback_data' => '/get_transaction_link ' . $order['id']]
                        ],
                        [
                            ['text' => 'Продовжити покупку', 'callback_data' => '/get_flowers']
                        ]
                    ]
                ];

                $post_data = [
                    'chat_id' => $this->chat_id,
                    'text' => $text,
                    'reply_markup' => json_encode($reply_markup)
                ];
            }else{

                $post_data = [
                    'chat_id' => $this->chat_id,
                    'text' => $text
                ];
            }


            $this->sendMessages($post_data);
        }
    }


    public function sendMessages($post_data)
    {
        $token = Config::get('bot_token'); // Замініть на ваш токен
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_exec($ch);
        curl_close($ch);
    }

    public function sendPhoto($post_data){
        $token = Config::get('bot_token'); // Замініть на ваш токен
        $url = "https://api.telegram.org/bot{$token}/sendPhoto";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $body = curl_exec($ch);
        curl_close($ch);
        return $body;
    }

    public function sendWelcomeMessages()
    {
        $post_data = [
            'chat_id' => $this->chat_id,
            'text' => "Вітаємо в нашому магазині квітів! \n Чудові новинки вже чекаються на вас.",
        ];

        $this->sendMessages($post_data);
    }

    public function getTransaction(){
        $data_base = DataBase::getInstance();

        $data_base->setTable('customers');

        $customer = $data_base->where(['chat_id' => $this->chat_id])->one();

        $data_base->setTable('orders');

        $order = $data_base->where(['customer_id' => $customer['id'], 'status' => 0])->one();

        $mono = new Mono();
        $r = $mono->createTransaction($order['id']);

        $this->sendMessages([
            'chat_id' => $this->chat_id,
            'text' => $r['pageUrl'],
        ]);

    }

}