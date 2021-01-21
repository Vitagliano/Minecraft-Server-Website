<?php

namespace app\api\admin;

use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Block extends Model
{

    public function __construct()
    {
        parent::__construct();

        $this->table();
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
        if(empty($_POST['username']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe o usuário']);
        }

        $stmt = $this->getConnection()->prepare("INSERT INTO `website_users_blockeds`(`user_NAME`) VALUES (?);");
        $stmt->execute([$_POST['username']]);

        return Json::encode(['response' => 'ok', 'message' => 'Usuário bloqueado']);
    }

    public function remote($username)
    {
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_users_blockeds`(`user_NAME`) VALUES (?);");
        $stmt->execute([$username]);
    }

    public function delete()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição bloqueada']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Nenhuma requisição enviada']);
        }
        if(empty($_POST['id']))
        {
            return Json::encode(['response' => 'error', 'message' => 'ID não informado']);
        }

        $stmt = $this->getConnection()->prepare("DELETE FROM `website_users_blockeds` WHERE `user_ID`=?");
        $stmt->execute([$_POST['id']]);

        return Json::encode(['response' => 'ok', 'message' => 'Usuário desbloqueado']);
    }

    public function has($username)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_users_blockeds` WHERE `user_NAME`=?");
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }

    public function show()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_users_blockeds`");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result) {
            $table.="<tr id='card-table-{$result->user_ID}'>
                        <th scope=\"row\">{$result->user_ID}</th>
                        <td><img src=\"https://minotar.net/helm/{$result->user_NAME}/30.png\"></td>
                        <td>{$result->user_NAME}</td>
                        <td><button class=\"btn btn-sm btn-block btn-danger block-delete\" id='{$result->user_ID}'>remover bloqueio</button></td>
                    </tr>";
        }
        return $table;
    }

    private function table()
    {
        $stmt = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_users_blockeds` ( `user_ID` INT(11) NOT NULL AUTO_INCREMENT ,  `user_NAME` VARCHAR(16) NOT NULL ,    PRIMARY KEY  (`user_ID`)) ENGINE = InnoDB;");
        $stmt->execute();
    }
}