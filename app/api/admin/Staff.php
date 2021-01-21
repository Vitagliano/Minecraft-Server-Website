<?php

namespace app\api\admin;

use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Staff
{

    private $model;

    public function __construct()
    {
        $this->model = new Model();
        $this->tables();
    }

    public function delete($type)
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição negada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição negada!']);
        }

        $table = "";
        $column = "";
        switch ($type)
        {
            case 'member':
                $table = "website_staffs_members";
                $column = "member_ID";
                break;
            case 'office':
                $table = "website_staffs";
                $column = "staff_ID";
                break;
        }

        $stmt = $this->model->getConnection()->prepare("DELETE FROM `{$table}` WHERE `{$column}` = ?");
        return $stmt->execute([ $_POST['id'] ]);
    }

    public function edit()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição negada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição negada!']);
        }

        $query = "";
        $exec = [  ];
        switch ($_POST['type'])
        {
            case 'office':
                $query = "UPDATE `website_staffs` SET `staff_NAME`=?,`staff_COLOR`=? WHERE `staff_ID`=?";
                $exec = [ $_POST['name'], $_POST['color'], $_POST['id'] ];
                break;
            case 'member':
                $query = "UPDATE `website_staffs_members` SET `member_NAME`=?,`member_OFFICE`=?,`member_TWITTER`=? WHERE `member_ID`=?";
                $exec = [ $_POST['name'], $_POST['office'], $_POST['twitter'], $_POST['id'] ];
                break;
        }

        $stmt = $this->model->getConnection()->prepare($query);
        $stmt->execute($exec);

        return Json::encode(['response' => 'ok', 'message' => 'Editado!']);
    }

    public function addOffice()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição negada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição negada!']);
        }
        if(empty($_POST['name']) || empty($_POST['color']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe os dados']);
        }

        $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_staffs`(`staff_NAME`, `staff_COLOR`) VALUES (?, ?)");
        $stmt->execute([ $_POST['name'], $_POST['color'] ]);

        return Json::encode(['response' => 'ok', 'message' => 'Cargo adicionado']);
    }

    public function listOffices()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_staffs`");
        $stmt->execute();
        $html = "";
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($fetch as $result) {
            $html .= "<tr>
                        <td>{$result->staff_NAME}</td>
                        <td>{$result->staff_COLOR}</td>
                        <td>
                            <button class=\"btn btn-sm btn-warning\" data-toggle=\"modal\" data-target=\"#mdc-{$result->staff_ID}\"><i class=\"fa fa-edit\"></i></button>
                            <button class=\"btn btn-sm btn-danger office-delete\" id='{$result->staff_ID}'><i class=\"fa fa-remove\"></i></button>
                        </td>
                        <div class=\"modal fade\" id=\"mdc-{$result->staff_ID}\" tabindex=\"-1\" role=\"dialog\">
                            <div class=\"modal-dialog\" role=\"document\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\" id=\"exampleModalLabel\">Editar cargo</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                            <span aria-hidden=\"true\">&times;</span>
                                        </button>
                                    </div>
                                    <div class=\"modal-body\">
                                        <form method=\"post\" class=\"editStaff\">
                                            <input type='hidden' name='type' value='office'>  
                                            <input type='hidden' name='id' value='{$result->staff_ID}'>
                                            <div class=\"form-group\">
                                                <label>Nome do cargo:</label>
                                                <input class=\"form-control\" name=\"name\" value=\"{$result->staff_NAME}\">
                                            </div>
                                            <div class=\"form-group\">
                                                <label>Cor:</label>
                                                <input class=\"form-control\" name=\"color\" placeholder=\"Exemplos: #000 ou rgba(0,0,0,1) ou rgb(0,0,0)\" value=\"{$result->staff_COLOR}\">
                                            </div>
                                            <button class=\"btn btn-sm btn-primary\">salvar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </tr>";
        }
        return $html;
    }

    public function addMember()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição negada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Requisição negada!']);
        }
        if(empty($_POST['name']) || empty($_POST['office']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe os dados']);
        }

        $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_staffs_members`(`member_NAME`, `member_OFFICE`, `member_TWITTER`) VALUES (?, ?, ?)");
        $stmt->execute([ $_POST['name'], $_POST['office'], $_POST['twitter'] ]);

        return Json::encode(['response' => 'ok', 'message' => 'Membro adicionado']);
    }

    public function listMembers()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_staffs_members`");
        $stmt->execute();
        $html = "";
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($fetch as $result) {
            $html .= "<tr>
                        <td>{$result->member_NAME}</td>
                        <td>{$this->id2Office($result->member_OFFICE)}</td>
                        <td>
                            <button class=\"btn btn-sm btn-warning\" data-toggle=\"modal\" data-target=\"#mdm-{$result->member_ID}\"><i class=\"fa fa-edit\"></i></button>
                            <button class=\"btn btn-sm btn-danger member-delete\" id='{$result->member_ID}'><i class=\"fa fa-remove\"></i></button>
                        </td>
                        <div class=\"modal fade\" id=\"mdm-{$result->member_ID}\" tabindex=\"-1\" role=\"dialog\">
                            <div class=\"modal-dialog\" role=\"document\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\" id=\"exampleModalLabel\">Editar membro</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                            <span aria-hidden=\"true\">&times;</span>
                                        </button>
                                    </div>
                                    <div class=\"modal-body\">
                                        <form method=\"post\" class=\"editStaff\">
                                            <input type='hidden' name='type' value='member'> 
                                            <input type='hidden' name='id' value='{$result->member_ID}'>
                                            <div class=\"form-group\">
                                                <label>Nome do membro:</label>
                                                <input class=\"form-control\" name=\"name\" value=\"{$result->member_NAME}\">
                                            </div>
                                            <div class=\"form-group\">
                                                <label>Twitter do membro:</label>
                                                <input class=\"form-control\" name=\"twitter\" value='{$result->member_TWITTER}'>
                                            </div>  
                                            <div class=\"form-group\">
                                                <label>Cargo</label>
                                                <select class=\"form-control\" name=\"office\">
                                                    <option value='{$result->member_OFFICE}' selected>Manter cargo</option>
                                                    {$this->select()}
                                                </select>
                                            </div>
                                            <button class=\"btn btn-sm btn-primary\">salvar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </tr>";
        }
        return $html;
    }

    public function select()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_staffs`");
        $stmt->execute();
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $select = "";
        foreach ($fetch as $result)
        {
            $select .= "<option value='{$result->staff_ID}'>{$result->staff_NAME}</option>";
        }
        return $select;
    }

    public function id2Office($id)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_staffs` WHERE `staff_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->staff_NAME;
    }


    private function tables()
    {
        $offices = $this->model->getConnection()->prepare("CREATE TABLE `website_staffs` ( `staff_ID` INT(11) NOT NULL AUTO_INCREMENT , `staff_NAME` VARCHAR(50) NOT NULL , `staff_COLOR` VARCHAR(32) NOT NULL , PRIMARY KEY (`staff_ID`)) ENGINE = InnoDB;");
        $offices->execute();

        $staffs = $this->model->getConnection()->prepare("CREATE TABLE `website_staffs_members` ( `member_ID` INT(11) NOT NULL AUTO_INCREMENT , `member_NAME` VARCHAR(16) NOT NULL , `member_OFFICE` INT(11) NOT NULL , `member_TWITTER` VARCHAR(120) NOT NULL , PRIMARY KEY (`member_ID`)) ENGINE = InnoDB;");
        $staffs->execute();
    }
}