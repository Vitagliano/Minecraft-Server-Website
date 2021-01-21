<?php

namespace app\api\admin;

use app\lib\Forms;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Discounts
{

    private $model;

    public function __construct()
    {
        $this->model = new Model();
        $this->deleteExpired();
        $this->tables();
    }

    public function add()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição bloqueada']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Nenhuma requisição enviada']);
        }
        if(isset($_POST['type']))
        {
            $type = $_POST['type'];
            if($type == 1)
            {
                if(Forms::isEmpty($_POST, [ 'cupom', 'percent', 'expire' ]))
                {
                    return Json::encode(['response' => 'error', 'message' => 'Insira todos campos']);
                }
            }
            if($type == 2)
            {

                if(Forms::isEmpty($_POST, [ 'cupom', 'percent', 'use' ]))
                {
                    return Json::encode(['response' => 'error', 'message' => 'Insira todos campos']);
                }
            }
            if($type == 3)
            {
                if(Forms::isEmpty($_POST, [ 'percent', 'expire' ]))
                {
                    return Json::encode(['response' => 'error', 'message' => 'Insira todos campos']);
                }
            }
        }else{
            return Json::encode(['response' => 'error', 'message' => 'Escolha um tipo de cupom']);
        }

        $_POST['server'] = (isset($_POST['server'])) ? $_POST['server'] : 0;
        $_POST['use'] = (isset($_POST['use'])) ? $_POST['use'] : 0;
        $_POST['expire'] = (isset($_POST['expire'])) ? $_POST['expire'] : "0000-00-00";
        $_POST['cupom'] = (isset($_POST['cupom'])) ? $_POST['cupom'] : "";

        $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_discounts`(`discount_HASH`, `discount_TYPE`, `discount_PERCENT`, `discount_SERVER`, `discount_EXPIRE`, `discount_USE`) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_POST['cupom'], $_POST['type'], $_POST['percent'], $_POST['server'], $_POST['expire'], $_POST['use']]);

        return Json::encode(['response' => 'ok', 'message' => 'Desconto adicionado']);
    }

    public function delete()
    {
        if(!Security::ajax())
        {
            return Json::encode(['status' => 'error', 'message' => 'Requisição bloqueada']);
        }
        if(empty($_POST))
        {
            return Json::encode(['status' => 'error', 'message' => 'Nenhuma requisição enviada']);
        }
        $stmt = $this->model->getConnection()->prepare("DELETE FROM `website_discounts` WHERE `discount_ID`=?");
        $stmt->execute([$_POST['id']]);
        return Json::encode(['status' => 'ok', 'message' => 'Cupom deletado']);
    }

    public function table()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_discounts`");
        $stmt->execute();
        if($stmt->rowCount() == 0) {
            return "";
        }
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";
        foreach ($fetch as $rs)
        {
            $hash = (($rs->discount_HASH) == '') ? '-/-' : $rs->discount_HASH;
            $date = ($rs->discount_EXPIRE == '0000-00-00') ? '-/-' : date('d/m/Y', strtotime($rs->discount_EXPIRE));
            $table.="<tr id='card-table-{$rs->discount_ID}'>
                        <th scope=\"row\">{$this->getTypeString($rs->discount_TYPE)}</th>
                        <td>{$hash}</td>
                        <td>{$rs->discount_PERCENT}%</td>
                        <td>{$date}</td>
                        <td>{$rs->discount_USE}</td>
                        <td>
                            <center><button class=\"btn btn-danger btn-sm discount-delete\" id='{$rs->discount_ID}' data-toggle=\"tooltip\" data-placement=\"top\" title=\"Deletar permanentemente\">deletar</button></center>
                        </td>
                    </tr>";
        }
        return $table;
    }

    public function setGlobal($server, $amount)
    {
        if($this->hasGlobalAll()) { $server = 0; }
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_discounts` WHERE `discount_TYPE`=? AND `discount_SERVER`=?");
        $stmt->execute([3, $server]);

        $rs = $stmt->fetchObject();

        $percent  = $rs->discount_PERCENT;
        $discount = ($amount * $percent) / 100;

        return $amount - $discount;
    }

    public function hasGlobal($server)
    {
        if($this->hasGlobalAll()) { return true; }
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_discounts` WHERE `discount_TYPE`=? AND `discount_SERVER`=?");
        $stmt->execute([3, $server]);
        return $stmt->rowCount() > 0;
    }

    public function hasGlobalAll()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_discounts` WHERE `discount_TYPE`=? AND `discount_SERVER`=?");
        $stmt->execute([3, 0]);
        return $stmt->rowCount() > 0;
    }

    public function apply($str, $type, $amount)
    {
        $percent = $this->percent($str);
        $discount = ($amount * $percent) / 100;

        if ($type == 2) {
            $this->removeUse($str);

        }

        return $amount - $discount;
    }

    public function getType($str)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_discounts` WHERE `discount_HASH`=?");
        $stmt->execute([$str]);
        return $stmt->fetchObject()->discount_TYPE;
    }

    public function has($str)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_discounts` WHERE `discount_HASH`=?");
        $stmt->execute([$str]);
        return $stmt->rowCount() > 0;
    }

    public function addUsage($cupom)
    {
        $stmt = $this->model->getConnection()->prepare("UPDATE `website_discounts` SET `discount_USE`=`discount_USE`+1 WHERE `discount_HASH`=?");
        $stmt->execute([$cupom]);
    }

    private function removeUse($str)
    {
        $stmt = $this->model->getConnection()->prepare("UPDATE `website_discounts` SET `discount_USE`=-`discount_USE` WHERE `discount_HASH`=?");
        $stmt->execute([$str]);
    }

    private function percent($str)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_discounts` WHERE `discount_HASH`=?");
        $stmt->execute([$str]);
        return $stmt->fetchObject()->discount_PERCENT;
    }

    private function deleteExpired()
    {
        $stmt = $this->model->getConnection()->prepare("DELETE FROM `website_discounts` WHERE `discount_EXPIRE` < ?");
        $stmt->execute([ date("Y-m-d") ]);

        $this->deleteUsed();
    }

    private function deleteUsed()
    {
        $stmt = $this->model->getConnection()->prepare("DELETE FROM `website_discounts` WHERE `discount_TYPE` = ? AND `discount_USE` = ?");
        $stmt->execute([ 2, 0 ]);
    }

    private function getTypeString($i)
    {
        $str = "";
        switch ($i)
        {
            case 1:
                $str = 'Cupom';
                break;
            case 2:
                $str = 'Liquidação';
                break;
            case 3:
                $str = 'Global';
                break;
        }
        return $str;
    }

    private function tables()
    {
        $stmt = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_discounts` ( `discount_ID` INT(11) NOT NULL AUTO_INCREMENT , `discount_HASH` VARCHAR(50) NOT NULL , `discount_TYPE` INT(1) NOT NULL , `discount_PERCENT` DECIMAL(10,2) NOT NULL , `discount_SERVER` INT(11) NOT NULL , `discount_EXPIRE` DATE NOT NULL , `discount_USE` INT(11) NOT NULL , PRIMARY KEY (`discount_ID`)) ENGINE = InnoDB;");
        $stmt->execute();
    }
}