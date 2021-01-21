<?php

namespace app\api\admin;


use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Servers
{

    protected $model;

    public function __construct()
    {
        $this->model = new Model();
        $this->table();
    }

    public function add()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        if(empty($_POST['name']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe o nome do servidor']);
        }

        $show = (isset($_POST['show'])) ? 1 : 0;

        $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_servers`(`server_NAME`, `server_SHOW`) VALUES (?, ?)");
        $stmt->execute([$_POST['name'], $show]);

        return Json::encode(['response' => 'ok', 'message' => 'Servidor adicionado']);
    }

    public function edit()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        if(empty($_POST['name']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe o nome do servidor']);
        }

        $stmt = $this->model->getConnection()->prepare("UPDATE `website_servers` SET `server_NAME`=? WHERE `server_ID`=?;");
        $stmt->execute([$_POST['name'], $_POST['id']]);

        return Json::encode(['response' => 'ok', 'message' => 'Servidor atualizado']);
    }

    public function delete()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        if(empty($_POST['id']))
        {
            return Json::encode(['response' => 'error', 'message' => 'ID não informado']);
        }

        $stmt = $this->model->getConnection()->prepare("DELETE FROM `website_servers` WHERE `server_ID`=?;");
        $stmt->execute([$_POST['id']]);

        return Json::encode(['response' => 'ok', 'message' => 'Servidor deletado']);
    }

    public function show()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM website_servers");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result)
        {
            $show = ($result->server_SHOW == 1) ? 'sim' : 'não';

            $table.="<tr id='card-table-{$result->server_ID}'>
                        <th scope=\"row\">{$result->server_ID}</th>
                        <td><form class=\"editServer\"><input type=\"hidden\" name=\"id\" value=\"{$result->server_ID}\"><input class=\"form-control form-control-sm\" name=\"name\" value=\"{$result->server_NAME}\"></form></td>
                        <td>{$show}</td>
                        <td><button class=\"btn btn-danger btn-block btn-sm server-delete\" id='{$result->server_ID}' type='button'>deletar</button></td>
                    </tr>";
        }

        return $table;
    }

    public function li()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_servers`");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $li = "";

        foreach ($fetch as $result)
        {
            $li.="<option value='{$result->server_ID}'>{$result->server_NAME}</option>";
        }

        return $li;
    }

    public function name($id)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_servers` WHERE `server_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->server_NAME;
    }

    private function table()
    {
        $stmt = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_servers` ( `server_ID` INT(11) NOT NULL AUTO_INCREMENT , `server_NAME` VARCHAR(50) NOT NULL , `server_SHOW` INT(1) NOT NULL DEFAULT '1' , PRIMARY KEY (`server_ID`)) ENGINE = InnoDB;");
        $stmt->execute();
    }

}