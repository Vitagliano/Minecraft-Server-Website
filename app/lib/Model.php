<?php

namespace app\lib;

use PDO;

class Model extends Config {

    private $con;

    public function __construct() {
        try {
            $this->con = new PDO("mysql:host=".self::DBHOST.";dbname=".self::DBNAME, self::DBUSER, self::DBPASS);
            $this->con->exec("set names utf8");
        } catch (\PDOException $e) {
            define('APP_ERROR', 'Erro na conexão com o banco de dados: <br><pre>'.$e->getMessage()."</pre>");
            include("./app/content/site/layouts/error.phtml");
            exit();
        }
    }

    public function set($host, $user, $pass, $db) {
        try {
            $this->con = new PDO("mysql:host=".$host.";dbname=".$db, $user, $pass);
            $this->con->exec("set names utf8");
        } catch (\PDOException $e) {
            define('APP_ERROR', 'Erro na conexão com o banco de dados: <br><pre>'.$e->getMessage()."</pre>");
            include("./app/content/site/layouts/error.phtml");
            exit();
        }
    }

    public function getConnection() {
        return $this->con;
    }

    public function getNewConnection() {
        return $this->con;
    }

    public function closeConnection() {
        $this->con = null;
    }

}