<?php

namespace app\services\base;

use app\services\Config;

class DataBase
{
    private static $instance;

    private $connect;

    public $table;

    private $where;

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new DataBase();
        }

        return self::$instance;
    }

    public function __construct(){
        $this->connect = new \mysqli(Config::get('db_host'), Config::get('db_user'), Config::get('db_password'), Config::get('db_name'));

        if ($this->connect->connect_error) {
            throw new \Exception('Не вдається підключитися до бази даних');
        }

    }

    public function setTable($table){
        $this->table = $table;
        return $this;
    }

    function insert($params) {
        $keys = array_keys($params);
        $prepare_value = str_repeat("?, ", count($keys));
        $prepare_value = trim($prepare_value, ", ");
        $keys = implode("`, `", $keys);
        $keys = '`' . $keys . '`';
        $stmt = $this->connect->prepare("INSERT INTO " . $this->table . " (" . $keys . ") VALUES (" . $prepare_value .")");

        $types = '';
        $data = [];
        foreach ($params as $key => $param) {
            switch (gettype($param)){
                case 'integer':
                    $types .= 'i';
                    break;
                case 'double':
                    $types .= 'd';
                    break;
                default:
                    $types .= 's';
                    break;
            }

            $data[] = &$params[$key];
        }

        array_unshift($data, $types);

        call_user_func_array([$stmt, 'bind_param'], $data);

        if ($stmt->execute()) {
            $last_id = $stmt->insert_id;
            //echo "Запис додано успішно!" . $last_id;
        } else {
            echo "Помилка: " . $stmt->error;
        }
        $stmt->close();
        return $last_id;
    }

    function delete($params) {

        $types = '';
        $prepare_value = [];
        $data = [];
        foreach ($params as $key => $param) {

            $prepare_value[] = $key . ' = ?';

            switch (gettype($param)){
                case 'integer':
                    $types .= 'i';
                    break;
                case 'double':
                    $types .= 'd';
                    break;
                default:
                    $types .= 's';
                    break;
            }

            $data[] = &$params[$key];
        }

        $prepare_value = implode(" AND ", $prepare_value);

        array_unshift($data, $types);

        $stmt = $this->connect->prepare("DELETE FROM " . $this->table . " WHERE " . $prepare_value);

        call_user_func_array([$stmt, 'bind_param'], $data);

        if ($stmt->execute()) {
            //$last_id = $stmt->insert_id;
            //echo "Запис додано успішно!" . $last_id;
        } else {
            echo "Помилка: " . $stmt->error;
        }
        $stmt->close();
        return true;
    }

    public function all(){

        $sql = $this->getSelectSql();

        $result = $this->connect->query($sql);

        return $result->fetch_assoc();
    }

    public function one(){
        $sql = $this->getSelectSql();

        $sql .= ' LIMIT 1';

        $result = $this->connect->query($sql);

        return $result->fetch_assoc();
    }

    private function getSelectSql(){
        if(!empty($this->where)){
            $where = [];
            foreach ($this->where as $key => $item) {
                if(gettype($item) == 'string'){
                    $where[] = $key . ' = \'' . $item . '\'';
                }else{
                    $where[] = $key . ' = ' . $item;
                }
            }

            $where = implode(' AND ', $where);

            $sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $where;
        }else{
            $sql = 'SELECT * FROM ' . $this->table;
        }

        return $sql;
    }

    public function where($where_data){
        foreach ($where_data as $key => $value) {
            $this->where[$key] = $this->connect->real_escape_string($value);
        }

        return $this;
    }


}