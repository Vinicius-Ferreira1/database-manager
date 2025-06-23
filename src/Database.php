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
            error_log("Database connection error: " . $e->getMessage());
            throw new \Exception("Database connection failed. Please try again later."); // Re-throw a generic exception
        }
    }

    public function execute($query, $params = []){
        try{
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        }catch(PDOException $e){
            error_log("Database query error: " . $e->getMessage() . " Query: " . $query . " Params: " . json_encode($params));
            throw new \Exception("A database error occurred during your request.");
        }
    }

    public function insert($values){

        $fields = array_keys($values);
        $binds = array_pad([], count($fields), '?');

        $sql = "INSERT INTO ".$this->table." (".implode(',', $fields).") VALUES (".implode(',', $binds).")";

        $this->execute($sql, array_values($values));

        return $this->connection->lastInsertId();
    }

    public function select($join = null, $where = null, $order = null, $limit = null, $fields = '*', $free = null, $params = []){
        if($free){
            return $this->execute($free, $params);
        }
        $where = strlen($where) ? "WHERE ".$where : '';
        $order = strlen($order) ? "ORDER BY ".$order : '';
        $limit = strlen($limit) ? "LIMIT ".$limit : '';

        $sql = "SELECT ".$fields." FROM ".$this->table." ". $join ." ".$where." ".$order." ".$limit;
        return $this->execute($sql, $params);
    }

    public function update($where, $values, $whereParams = []){
        $setFields = [];
        $setParams = [];

        foreach ($values as $field => $value) {
            $setFields[] = "`{$field}` = :set_{$field}";
            $setParams[":set_{$field}"] = $value;
        }
        $setClause = implode(', ', $setFields);

        $allParams = array_merge($setParams, $whereParams);
        
        $whereSql = strlen($where) ? "WHERE ".$where : '';
        
        $sql = "UPDATE ".$this->table." SET ".$setClause." ".$whereSql;

        $this->execute($sql, $allParams);

        return true;
    }

    public function delete($where, $params = []){
        $sql = "DELETE FROM ".$this->table." WHERE ".$where;

        $this->execute($sql, $params);

        return true;
    }

}
