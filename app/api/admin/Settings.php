<?php

namespace app\api\admin;

use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Settings
{

    private $model;

    public function  __construct()
    {
        $this->model = new Model();
        $this->tables();
    }

    public function setTerms()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        $stmt = $this->model->getConnection()->prepare("UPDATE `website_settings` SET `setting_TERMS`=? WHERE `setting_ID`=?");
        $stmt->execute([$_POST['html'], 1]);
        return Json::encode(['response' => 'ok', 'message' => 'Termos e Condições atualizados']);
    }

    public function printTerms()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings` WHERE `setting_ID`=?");
        $stmt->execute([1]);
        return $stmt->fetchObject()->setting_TERMS;
    }

    public function printDatabases($database)
    {
        $type = "";
        switch ($database) {
            case 'authme':
                $type = "database_AUTHME";
                break;
            case 'litebans':
                $type = "database_LITEBANS";
                break;
        }

        $sql = "SELECT * FROM `website_settings_databases` WHERE `database_ID`=1";

        $stmt = $this->model->getConnection()->prepare($sql);
        $stmt->execute();

        return Json::decode($stmt->fetchObject()->$type);
    }

    public function saveDatabases()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }

        $gateway = $_POST['type'];

        $type = "";
        $json = Json::encode([ 'host' => $_POST['host'], 'username' => $_POST['username'], 'password' => $_POST['password'], 'database' => $_POST['database'] ]);

        switch ($gateway) {
            case 'authme':
                $type = "database_AUTHME";
                break;
            case 'litebans':
                $type = "database_LITEBANS";
                break;
        }

        $sql = "UPDATE `website_settings_databases` SET `{$type}`=? WHERE `database_ID`=?";
        $stmt = $this->model->getConnection()->prepare($sql);
        $stmt->execute([ $json , 1 ]);

        return Json::encode(['response' => 'ok']);
    }

    public function printGateway($gateway)
    {
        $type = "";
        switch ($gateway) {
            case 'pagseguro':
                $type = "gateway_PAGSEGURO_DATA";
                break;
            case 'mercadopago':
                $type = "gateway_MERCADOPAGO_DATA";
                break;
            case 'paypal':
                $type = "gateway_PAYPAL_DATA";
                break;
        }

        $sql = "SELECT * FROM `website_settings_gateways` WHERE `gateway_ID`=1";

        $stmt = $this->model->getConnection()->prepare($sql);
        $stmt->execute();

        return Json::decode($stmt->fetchObject()->$type);
    }

    public function saveGateways()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }

        $gateway = $_POST['type'];

        $type = "";
        $json = [];

        switch ($gateway) {
            case 'pagseguro':
                $type = "gateway_PAGSEGURO_DATA";
                $json = Json::encode(['email' => $_POST['email'], 'token' => $_POST['token']]);
                break;
            case 'mercadopago':
                $type = "gateway_MERCADOPAGO_DATA";
                $json = Json::encode(['client_id' => $_POST['id'], 'client_secret' => $_POST['secret']]);
                break;
            case 'paypal':
                $type = "gateway_PAYPAL_DATA";
                $json = Json::encode(['email' => $_POST['email'], 'password' => $_POST['password'], 'signature' => $_POST['signature']]);
                break;
        }

        $sql = "UPDATE `website_settings_gateways` SET `{$type}`=? WHERE `gateway_ID`=?";
        $stmt = $this->model->getConnection()->prepare($sql);
        $stmt->execute([ $json , 1 ]);

        return Json::encode(['response' => 'ok']);
    }

    public function activeGateway()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }

        $mode = empty($_POST['mode']) ? 0 : 1;
        $type = "";
        switch ($_POST['gateway']) {
            case 'pagseguro':
                $type = "gateway_PAGSEGURO_MODE";
                break;
            case 'mercadopago':
                $type = "gateway_MERCADOPAGO_MODE";
                break;
            case 'paypal':
                $type = "gateway_PAYPAL_MODE";
                break;
        }

        $sql = "UPDATE `website_settings_gateways` SET `{$type}`=? WHERE `gateway_ID`=?";

        $stmt = $this->model->getConnection()->prepare($sql);
        $stmt->execute([ $mode, 1 ]);

        return "ok";
    }

    public function gatewaysChecked($gateway)
    {
        $type = "";
        switch ($gateway) {
            case 'pagseguro':
                $type = "gateway_PAGSEGURO_MODE";
                break;
            case 'mercadopago':
                $type = "gateway_MERCADOPAGO_MODE";
                break;
            case 'paypal':
                $type = "gateway_PAYPAL_MODE";
                break;
        }
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings_gateways` WHERE `gateway_ID`=?");
        $stmt->execute([1]);
        return ($stmt->fetchObject()->$type == 1) ? "checked" : "";
    }

    public function gatewaysDisabled($gateway)
    {
        $type = "";
        switch ($gateway) {
            case 'pagseguro':
                $type = "gateway_PAGSEGURO_MODE";
                break;
            case 'mercadopago':
                $type = "gateway_MERCADOPAGO_MODE";
                break;
            case 'paypal':
                $type = "gateway_PAYPAL_MODE";
                break;
        }
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings_gateways` WHERE `gateway_ID`=?");
        $stmt->execute([1]);
        return ($stmt->fetchObject()->$type == 0) ? false : true;
    }

    private function generateGateways()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings_gateways`");
        $stmt->execute();
        if($stmt->rowCount() == 0) {
            $insert = $this->model->getConnection()->prepare("INSERT INTO `website_settings_gateways`(`gateway_PAGSEGURO_DATA`, `gateway_MERCADOPAGO_DATA`, `gateway_PAYPAL_DATA`) VALUES (?,?,?)");
            $insert->execute([ Json::encode([ 'email' => 'não registrado', 'token' => 'não registrado' ]), Json::encode([ 'client_id' => 'não registrado', 'client_secret' => 'não registrado' ]), Json::encode([ 'email' => 'não registrado', 'password' => 'não registrado', 'signature' => 'não registrado' ]) ]);
        }
    }

    private function generateDatabases()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings_databases`");
        $stmt->execute();
        if($stmt->rowCount() == 0) {
            $insert = $this->model->getConnection()->prepare("INSERT INTO `website_settings_databases`(`database_AUTHME`, `database_LITEBANS`) VALUES (?, ?)");
            $insert->execute([ Json::encode([ 'host' => 'não registrado', 'username' => 'não registrado', 'password' => 'não registrado', 'database' => 'não registrado' ]), Json::encode([ 'host' => 'não registrado', 'username' => 'não registrado', 'password' => 'não registrado', 'database' => 'não registrado' ]) ]);
        }
    }

    public function paypalGlobal()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings_gateways` WHERE `gateway_ID`=?");
        $stmt->execute([1]);
        return $stmt->fetchObject()->gateway_PAYPAL_GLOBAL == 1;
    }

    private function tables()
    {
        $gateways = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_settings_gateways` ( `gateway_ID` INT(11) NOT NULL AUTO_INCREMENT ,  `gateway_PAGSEGURO_MODE` INT(1) NOT NULL DEFAULT '0' ,  `gateway_MERCADOPAGO_MODE` INT(1) NOT NULL DEFAULT '0' ,  `gateway_PAYPAL_MODE` INT(1) NOT NULL DEFAULT '0' , `gateway_PAYPAL_GLOBAL` INT(1) NOT NULL DEFAULT '0' ,  `gateway_PAGSEGURO_DATA` TEXT NOT NULL ,  `gateway_MERCADOPAGO_DATA` TEXT NOT NULL ,  `gateway_PAYPAL_DATA` TEXT NOT NULL ,    PRIMARY KEY  (`gateway_ID`)) ENGINE = InnoDB;");
        $gateways->execute();
        $this->generateGateways();

        $databases = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_settings_databases` ( `database_ID` INT(11) NOT NULL AUTO_INCREMENT ,  `database_AUTHME` TEXT NOT NULL ,  `database_LITEBANS` TEXT NOT NULL ,    PRIMARY KEY  (`database_ID`)) ENGINE = InnoDB;");
        $databases->execute();
        $this->generateDatabases();

        $settings = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_settings` ( `setting_ID` INT(1) NOT NULL AUTO_INCREMENT , `settings_MAINTENANCE_MODE` INT(1) NOT NULL , `settings_MAINTENANCE` TEXT NOT NULL , `settings_MAINTENANCE_IPS` TEXT NOT NULL , `settings_EMAIL_PURCHASE_PENDING` TEXT NOT NULL , `settings_EMAIL_PURCHASE_CONFIRMATION` TEXT NOT NULL , `settings_EMAIL_PURCHASE_ACTIVE` TEXT NOT NULL , `settings_EMAIL_TICKET_OPENED` TEXT NOT NULL , `settings_EMAIL_TICKET_CLOSED` TEXT NOT NULL , `settings_EMAIL_TICKET_REPLIED` TEXT NOT NULL, `setting_TERMS` TEXT NOT NULL , PRIMARY KEY (`setting_ID`)) ENGINE = InnoDB;");
        $settings->execute();

        $verify = $this->model->getConnection()->prepare("SELECT * FROM `website_settings`");
        $verify->execute();
        if($verify->rowCount() == 0) {
            $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_settings`(`settings_MAINTENANCE_MODE`, `settings_MAINTENANCE`, `settings_MAINTENANCE_IPS`, `settings_EMAIL_PURCHASE_PENDING`, `settings_EMAIL_PURCHASE_CONFIRMATION`, `settings_EMAIL_PURCHASE_ACTIVE`, `settings_EMAIL_TICKET_OPENED`, `settings_EMAIL_TICKET_CLOSED`, `settings_EMAIL_TICKET_REPLIED`, `setting_TERMS`) VALUES (0, '.', '0.0.0.0', '.', '.', '.', '.', '.', '.', '.')");
            $stmt->execute();
        }

    }

}