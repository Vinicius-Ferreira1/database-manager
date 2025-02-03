<?php

namespace Lades\DatabaseManager;

use \PDO;
use \PDOException;

class Database{

    private static $host;
    private static $name;
    private static $user;
    private static $pass;
    private static $port;
    private $table;
    private $connection;
    
    public function __construct($table){
        $this->table = $table;
        $this->setConection();
    }

    public static function config($host, $name, $user, $pass = '', $port = 3306){
        self::$host = $host;
        self::$name = $name;
        self::$user = $user;
        self::$pass = $pass;
        self::$port = $port;
    }


    private function setConection(){
        try{
            $this->connection = new PDO("mysql:host=".self::$host.";dbname=".self::$name.";port=".self::$port, self::$user, self::$pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(PDOException $e){
            die("Erro: ".$e->getMessage());
        }
    }

    public function execute($query, $params = []){
        try{
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        }catch(PDOException $e){
            die("Erro: ".$e->getMessage());
        }
    }

    public function insert($values){

        $fields = array_keys($values);
        $binds = array_pad([], count($fields), '?');

        $sql = "INSERT INTO ".$this->table." (".implode(',', $fields).") VALUES (".implode(',', $binds).")";

        $this->execute($sql, array_values($values));

        return $this->connection->lastInsertId();
    }

    public function select($join = null, $where = null, $order = null, $limit = null, $fields = '*', $free = null){
        if($free){
            return $this->execute($free);
        }
        $where = strlen($where) ? "WHERE ".$where : '';
        $order = strlen($order) ? "ORDER BY ".$order : '';
        $limit = strlen($limit) ? "LIMIT ".$limit : '';

        $sql = "SELECT ".$fields." FROM ".$this->table." ". $join ." ".$where." ".$order." ".$limit;
        return $this->execute($sql);
    }

    public function update($where, $values){
        $fields = array_keys($values);

        $sql = "UPDATE ".$this->table." SET ".implode('=?,', $fields)."=? WHERE ".$where;

        $this->execute($sql, array_values($values));

        return true;
    }

    public function delete($where){
        $sql = "DELETE FROM ".$this->table." WHERE ".$where;

        $this->execute($sql);

        return true;
    }

}