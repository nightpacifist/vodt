<?php

namespace app;

class ClassLoader
{
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new ClassLoader();
        }

        spl_autoload_register([self::$instance, "load"]);
    }

    public function load($name) {

        self::loadEnv();
        if(stripos("app", $name) == 0){
            $name = substr($name, 4, strlen($name));
        }

        include_once($_SERVER["DOCUMENT_ROOT"] . "/" . str_replace("\\", "/", $name) . ".php");
    }



    public static function loadEnv(){

        $path = __DIR__ . '/.env';

        if (!file_exists($path)) {
            throw new Exception("Файл .env не знайдено");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ігноруємо коментарі
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Розділяємо рядок на ключ і значення
            list($name, $value) = explode('=', $line, 2);

            // Видаляємо пробіли і лапки, якщо є
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"");

            // Встановлюємо змінну оточення
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }

}