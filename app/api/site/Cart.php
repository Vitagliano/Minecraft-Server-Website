<?php

namespace app\api\site;

use app\api\admin\Discounts;
use app\lib\Config;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Cart
{

    private $pdo, $discounts;

    public function __construct()
    {
        $model = new Model;
        $this->discounts = new Discounts();
        $this->pdo = $model->getConnection();
        if (!isset($_SESSION['WebCart'])) {
            $_SESSION['WebCart'] = array();
        }
    }

    public function add()
    {
        if(!Security::ajax()) { return Json::encode(['response' => 'error', 'message' => 'You\'re blocked by security system']); }
        if(!isset($_POST)) { return Json::encode(['response' => 'error', 'message' => 'What method?']); }
        if(empty($_POST['id'])) { return Json::encode(['response' => 'error', 'message' => 'Empty params']); }

        $i = explode("-", $_POST['id']);

        if (empty($i[1])) {
            $indice = sprintf("%s:%s", $i[0], '0');
        } else {
            $indice = sprintf("%s:%s", (int)$i[0], (int)$i[1]);
        }
        if (!isset($_SESSION['WebCart'][$indice])) {
            $_SESSION['WebCart'][$indice] = 1;
        }else{
            $_SESSION['WebCart'][$indice] = $_SESSION['WebCart'][$indice] + 1;
        }
        return Json::encode(['response' => 'ok']);
    }

    public function att()
    {
        if(!Security::ajax()) { return Json::encode(['response' => 'error', 'message' => 'You\'re blocked by security system']); }
        if(!isset($_POST)) { return Json::encode(['response' => 'error', 'message' => 'What method?']); }
        if(empty($_POST['id'])) { return Json::encode(['response' => 'error', 'message' => 'Empty params']); }
        $indice = $_POST['id'];
        if($_POST['qnt'] == 0)
        {
            unset($_SESSION['WebCart'][$indice]);
        }else{
            $_SESSION['WebCart'][$indice] = $_POST['qnt'];
        }
        var_dump($_POST);
    }

    public function remove()
    {
        if(!Security::ajax()) { return Json::encode(['response' => 'error', 'message' => 'You\'re blocked by security system']); }
        if(!isset($_POST)) { return Json::encode(['response' => 'error', 'message' => 'What method?']); }
        if(empty($_POST['id'])) { return Json::encode(['response' => 'error', 'message' => 'Empty params']); }
        unset($_SESSION['WebCart'][$_POST['id']]);
    }

    public function listPackages()
    {
        $return = array();
        foreach ($_SESSION['WebCart'] as $indice => $qnt) {
            list($package, $server) = explode(":", $indice);
            $stmt = $this->pdo->prepare("SELECT * FROM `website_packages` WHERE `package_ID`=?");
            $stmt->execute([$package]);

            $result = $stmt->fetchObject();

            $return[$indice]['id'] = $result->package_ID;
            $return[$indice]['title'] = $result->package_NAME;
            if($this->discounts->hasGlobal($result->package_SERVER))
            {
                $return[$indice]['price'] = $this->discounts->setGlobal($result->package_SERVER, $result->package_AMOUNT);
            }else{
                $return[$indice]['price'] = $result->package_AMOUNT;
            }
            $return[$indice]['image'] = $result->package_IMAGE;
            $return[$indice]['server'] = $server;
            $return[$indice]['server_name'] = $this->getServername($result->package_SERVER);
            $return[$indice]['qnt']    = $qnt;
        }
        return $return;
    }

    public function totalAmount()
    {
        $packages = $this->listPackages();
        $total = 0;
        foreach ($packages as $indice => $linha) {
            $total += $linha['price']*$linha['qnt'];
        }
        return $total;
    }

    public function getServername($server)
    {
        if($server == 0) {
            return "Global";
        }
        $stmt = $this->pdo->prepare("SELECT * FROM `website_servers` WHERE `server_ID` = ?");
        $stmt->execute([$server]);

        return strtoupper($stmt->fetchObject()->server_NAME);
    }

    public function getString()
    {
        return (count($_SESSION['WebCart']) == 0) ? '<b>seu</b> carrinho está vazio' : "<b>".count($_SESSION['WebCart'])."</b> itens no carrinho";
    }

}