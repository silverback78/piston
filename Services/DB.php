<?php
require_once('Models/Config.php');

class DB {
    public static $sql;
    public static $config;
    public static $pdo = null;
    public static $query;
    public static $startTime;
    public static $endTime;
    public static $results;
    public static $executionTime;    
    
    public static function connect() {
        try {
            if (self::$pdo == null) {
                self::$pdo = new \PDO(
                    'mysql:host=' . Config::$dbHost . ';dbname=' . Config::$dbName,
                    Config::$dbUser,
                    Config::$dbPass,
                    array(
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                    )
                );
            }
        }
        catch (PDOException $e) {
            echo "Error : " . $e->getMessage() . "<br/>";
            die();
        }
    }

    public static function lastInsertId() {
        return self::$pdo->lastInsertId();
    }

    public static function execute() {
        self::$startTime = microtime(true);
        try {
            self::$query = self::$pdo->prepare(self::$sql);
            self::$query->execute();
        }
        catch (PDOException $e) {
            echo "Error : " . $e->getMessage() . "<br/>";
            self::closeConnection();
            die();
        }
        self::$endTime = microtime(true);
        self::$executionTime = self::$endTime - self::$startTime;
    }

    public static function closeConnection() {
        self::$sql = null;
        self::$pdo = null;
    }

    public static function executeQuery($key, $sql) {
        self::$sql = $sql;
        self::connect();
        self::execute();
        self::$results[$key] = self::$query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function executeSql($sql) {
        self::$sql = $sql;
        self::connect();
        self::execute();
    }
}

?>