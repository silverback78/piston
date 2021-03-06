<?php
require_once('Config.php');

class DB {
    public static $sql;
    public static $params;
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
        $dbname = Config::$dbName;
        $use = self::$pdo->prepare("use $dbname");
        $use->execute();
        self::$startTime = microtime(true);
        self::$query = self::$pdo->prepare(self::$sql);
        self::$query->execute(self::$params);
        self::$endTime = microtime(true);
        self::$executionTime = self::$endTime - self::$startTime;
    }

    public static function closeConnection() {
        self::$sql = null;
        self::$pdo = null;
    }

    public static function executeQuery($key, $sql, $params) {
        self::$sql = $sql;
        self::$params = $params;
        self::connect();
        self::execute();
        self::$results[$key] = self::$query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function executeSql($sql, $params) {
        self::$sql = $sql;
        self::$params = $params;
        self::connect();
        self::execute();
    }
}

?>