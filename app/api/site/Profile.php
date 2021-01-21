<?php

namespace app\api\site;

use app\api\admin\Settings;
use app\lib\Forms;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Profile
{

    public $model, $settings;

    public function __construct()
    {
        $this->model = new Model();
        $this->settings = new Settings();
    }

    public function login($inputs)
    {
        $forms = new Forms();
        if(!Security::ajax()) {
            return Json ::encode(['response' => 'error', 'message' => 'You\'re blocked by security system']);
        }
        if(!isset($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'What method?']);
        }
        if(empty($_POST[$inputs['username']]) || empty($_POST[$inputs['password']]))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe os dados']);
        }
        if(!$forms->checkValid($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Tentativa de ataque bloqueado']);
        }
        if(!$this->hasUsername($_POST[$inputs['username']]))
        {
            return Json::encode(['response' => 'error', 'message' => $_POST[$inputs['username']].' não está cadastrado']);
        }

        if(!$this->check($_POST[$inputs['username']], $_POST[$inputs['password']]))
        {
            return Json::encode(['response' => 'error', 'message' => 'Senha inválida']);
        }

        $this->setSession('WebUserLogin', $_POST[$inputs['username']]);

        return Json::encode(['response' => 'ok', 'message' => 'Redirecionando...']);
    }

    public function register($inputs)
    {
        $forms = new Forms();
        if(!Security::ajax()) {
            return Json ::encode(['response' => 'error', 'message' => 'You\'re blocked by security system']);
        }
        if(!isset($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'What method?']);
        }
        if(empty($_POST[$inputs['username']]) || empty($_POST[$inputs['email']]) || empty($_POST[$inputs['password']]) || empty($_POST[$inputs['repeat']]))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe os dados']);
        }
        if(!$forms->checkValid($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Tentativa de ataque bloqueado']);
        }
        if($this->hasUsername($_POST[$inputs['username']]))
        {
            return Json::encode(['response' => 'error', 'message' => $_POST[$inputs['username']].' já está cadastrado']);
        }
        if(!filter_var($_POST[$inputs['email']], FILTER_VALIDATE_EMAIL))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe um e-mail válido']);
        }
        if($_POST[$inputs['password']] != $_POST[$inputs['repeat']])
        {
            return Json::encode(['response' => 'error', 'message' => 'As senhas não estão iguais']);
        }
        $stmt = $this->model->getConnection()->prepare("INSERT INTO `authme`(`username`, `realname`, `password`, `email`) VALUES (?, ?, ?, ?)");
        $stmt->execute([ strtolower($_POST[$inputs['username']]), $_POST[$inputs['username']], $this->createHash($_POST[$inputs['password']]), $_POST[$inputs['email']] ]);
        return Json::encode(['response' => 'ok', 'message' => 'Registrado com sucesso! <br>Redirecionando...']);
    }

    public function isLogged()
    {
        return (isset($_SESSION['WebUserLogin'])) ? true : (isset($_COOKIE['WebUserLogin'])) ? true : false;
    }

    public function logout()
    {
        if(isset($_SESSION['WebUserLogin']))
        {
            unset($_SESSION['WebUserLogin']);
            return;
        }
        unset($_COOKIE['WebUserLogin']);
        setcookie('WebUserLogin', '', time() - 3600, '/');
        return;
    }

    public function username()
    {
        if(isset($_SESSION['WebUserLogin']))
        {
            return $_SESSION['WebUserLogin'];
        }
        return $_COOKIE['WebUserLogin'];
    }

    public function hasUsername($username)
    {
        $this->model->set($this->settings->printDatabases('authme')->host,
            $this->settings->printDatabases('authme')->username,
            $this->settings->printDatabases('authme')->password,
            $this->settings->printDatabases('authme')->database);
        $stmt = $this->model->getNewConnection()->prepare("SELECT * FROM `authme` WHERE `realname`=?");
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }

    public function check($username, $pass)
    {
        $this->model->set($this->settings->printDatabases('authme')->host,
            $this->settings->printDatabases('authme')->username,
            $this->settings->printDatabases('authme')->password,
            $this->settings->printDatabases('authme')->database);
        $stmt = $this->model->getNewConnection()->prepare("SELECT `password` FROM `authme` WHERE `realname`=?");
        $stmt->execute([$username]);
        $password = $stmt->fetchObject()->password;
        $shainfo = explode("$", $password);
        $pass    = hash("sha256", $pass) . $shainfo[2];
        return strcasecmp($shainfo[3], hash('sha256', $pass)) == 0;
    }

    private function setSession($name, $value, $mode = false)
    {
        if($mode)
        {
            return setcookie($name, $value, time()+3600*24*30, "/");
        }
        return $_SESSION[$name] = $value;
    }

    private function createHash($pass) {
        $salt = self::createSalt();
        return "\$SHA\$" . $salt . "\$" . hash("sha256", hash('sha256', $pass) . $salt);
    }

    private function createSalt() {
        $salt = "";
        for ($i = 0; $i < 20; $i++) {
            $salt .= rand(0, 9);
        }
        return substr(hash("sha1", $salt), 0, 16);
    }

}