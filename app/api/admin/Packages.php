<?php

namespace app\api\admin;

use app\api\Images;
use app\lib\Forms;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Packages extends Model
{

    private $servers;

    public function __construct()
    {
        parent::__construct();

        $this->servers = new Servers();

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
        if(Forms::isEmpty($_POST, [ 'name', 'description', 'amount', 'server' ]))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe as informações obrigatórias']);
        }
        $images = new Images();
        $image = $images->save($_FILES['image'], "./app/content/site/assets/images/packages/");
        $image = Json::decode($image);

        if(!$image->response)
        {
            return Json::encode([ 'response' => 'error', 'message' => $image->message ]);
        }

        $stmt = $this->getConnection()->prepare("INSERT INTO `website_packages`(`package_NAME`, `package_AMOUNT`, `package_DESCRIPTION`, `package_IMAGE`, `package_SERVER`, `package_EXPIRE`, `package_DATE`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([ $_POST['name'], $_POST['amount'], $_POST['description'], $image->src, $_POST['server'], $_POST['expire'], $_POST['validate'] ]);

        $id_package = $this->getConnection()->lastInsertId();
        $this->addCommands($id_package, $_POST['when'], $_POST['command'], $_POST['to'], $_POST['server']);

        return Json::encode([ 'response' => 'ok', 'message' => 'Pacote adicionado' ]);
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

        $stmt = $this->getConnection()->prepare("DELETE FROM `website_packages` WHERE `package_ID`=?;");
        $stmt->execute([$_POST['id']]);

        return Json::encode([ 'response' => 'ok', 'message' => 'Deletado com sucesso' ]);
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
        if(Forms::isEmpty($_POST, [ 'name', 'description', 'amount', 'server' ]))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe as informações obrigatórias']);
        }

        if(!empty($_FILES['image']))
        {
            $images = new Images();
            $image = $images->save($_FILES['image'], "./app/content/site/assets/images/packages/");
            $image = Json::decode($image);

            if(!$image->response)
            {
                return Json::encode([ 'response' => 'error', 'message' => $image->message ]);
            }

            $img_src = $image->src;
        }else{
            $img_src = $this->imageSRC($_POST['id']);
        }

        $stmt = $this->getConnection()->prepare("UPDATE `website_packages` SET `package_NAME`=?,`package_AMOUNT`=?,`package_DESCRIPTION`=?,`package_IMAGE`=?,`package_SERVER`=?,`package_EXPIRE`=?,`package_DATE`=? WHERE `package_ID`=?");
        $stmt->execute([ $_POST['name'], $_POST['amount'], $_POST['description'], $img_src, $_POST['server'], $_POST['expire'], $_POST['validate'], $_POST['id'] ]);

        return Json::encode([ 'response' => 'ok', 'message' => 'Pacote atualizado' ]);
    }

    private function imageSRC($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages` WHERE `package_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->package_IMAGE;
    }

    private function addCommands($package, $when, $command, $server, $sid)
    {
        $i = -1;
        foreach ($when as $w => $type)
        {
            $i++;

            if($server[$i] == 'atual')
            {
                $ss = $sid;
            }else{
                $ss = $server[$i];
            }

            $stmt = $this->getConnection()->prepare("INSERT INTO `website_packages_commands`(`command_PACKAGE`, `command_SERVER`, `command_TYPE`, `command_TOSEND`) VALUES (?, ?, ?, ?)");
            $stmt->execute([ $package, $ss, $type, $command[$i] ]);

            $command[$i];
        }
    }

    public function tables()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages`");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result) {
            $amount = "R$".number_format($result->package_AMOUNT, 2, ',', '.');
            $server = $this->servers->name($result->package_SERVER);
            $table .= "<tr id='card-table-{$result->package_ID}'>
                            <th scope=\"row\">{$result->package_ID}</th>
                            <td>{$result->package_NAME}</td>
                            <td>{$server}</td>
                            <td>{$amount}</td>
                            <td>
                                <button class=\"btn btn-sm btn-warning\" data-toggle=\"modal\" data-target=\"#mp-{$result->package_ID}\">editar</button>
                                <button class=\"btn btn-sm btn-danger package-delete\" id='{$result->package_ID}'>deletar</button>
                            </td>
                            <div class=\"modal fade\" id=\"mp-{$result->package_ID}\" tabindex=\"-1\" role=\"dialog\">
                                <div class=\"modal-dialog modal-lg\" role=\"document\">
                                    <div class=\"modal-content\">
                                        <div class=\"modal-header\">
                                            <h5 class=\"modal-title\" id=\"exampleModalLabel\">Editar pacote</h5>
                                            <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                                <span aria-hidden=\"true\">&times;</span>
                                            </button>
                                        </div>
                                        <div class=\"modal-body\">
                                            <form method=\"post\" enctype=\"multipart/form-data\" class=\"editPackage\">
                                                <input type='hidden' name='id' value='{$result->package_ID}'>
                                                <div class=\"form-group\">
                                                    <label>Nome *</label>
                                                    <input class=\"form-control\" value='{$result->package_NAME}' name=\"name\" autocomplete=\"false\">
                                                </div>
                                                <div class=\"form-group\">
                                                    <label>Descrição *</label>
                                                    <textarea class=\"summernote\" name=\"description\">{$result->package_DESCRIPTION}</textarea>
                                                </div>
                                                <div class=\"form-group\">
                                                    <hr>
                                                    <label>Imagem *</label>
                                                    <div class=\"input-group mb-3\">
                                                        <div class=\"custom-file\">
                                                            <input type=\"file\" class=\"custom-file-input imagePicker\" name=\"image\">
                                                            <label class=\"custom-file-label\" for=\"inputGroupFile02\">Escolha a imagem</label>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <p class=\"text-muted\">Preview:</p>
                                                    <div class=\"renderImage\"><img src=\"{$result->package_IMAGE}\" width=\"200px\" class=\"img-thumbnail img-fluid changeImage\"></div>
                                                    <hr>
                                                </div>
                                                <div class=\"form-group\">
                                                    <label>Valor *</label>
                                                    <input class=\"form-control\" name=\"amount\" value='{$result->package_AMOUNT}' type=\"number\" step=\"any\">
                                                </div>
                                                <div class=\"form-group\">
                                                    <label>Servidor *</label>
                                                    <select class=\"form-control\" name=\"server\">
                                                        <option value='{$result->package_SERVER}' selected>Manter</option>
                                                        " . $this->servers->li() . "
                                                    </select>
                                                </div>
                                                <div class=\"form-group\">
                                                    <label>Expira em</label>
                                                    <input class=\"form-control\" name=\"expire\" value='{$result->package_EXPIRE}' type=\"number\" placeholder=\"Em dias\">
                                                    <small class=\"text-muted\"><small>Caso o pacote tenha um prazo, adicione em dias. Logo, será necessário a ação \"Expirar\"</small></small>
                                                </div>
                                                <div class=\"form-group\">
                                                    <label>Válido até</label>
                                                    <input class=\"form-control\" name=\"validate\" value='{$result->package_DATE}' type=\"date\">
                                                    <small class=\"text-muted\"><small>Se o pacote for temporário, coloque a data final que ele ficara na loja.</small></small>
                                                </div>
                                                <button class=\"btn btn-primary\">Editar pacote</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tr>";
        }
        return $table;
    }

    public function options()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages` WHERE `package_SERVER`=?");
        $stmt->execute([$_POST['id']]);
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";
        foreach ($fetch as $result)
        {
            $table .= "<option value='{$result->package_ID}'>{$result->package_NAME}</option>";
        }
        return $table;
    }

    private function table()
    {
        $packages = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_packages` ( `package_ID` INT(11) NOT NULL AUTO_INCREMENT , `package_NAME` VARCHAR(50) NOT NULL , `package_AMOUNT` DECIMAL(10,2) NOT NULL , `package_DESCRIPTION` TEXT NOT NULL , `package_IMAGE` VARCHAR(255) NOT NULL , `package_SERVER` INT(11) NOT NULL , `package_EXPIRE` INT(11) NOT NULL DEFAULT '0' , `package_DATE` DATE NULL DEFAULT NULL , PRIMARY KEY (`package_ID`)) ENGINE = InnoDB;");
        $packages->execute();

        $commands = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_packages_commands` ( `command_ID` INT(11) NOT NULL AUTO_INCREMENT , `command_PACKAGE` INT(11) NOT NULL , `command_SERVER` INT(11) NOT NULL , `command_TYPE` INT(1) NOT NULL , `command_TOSEND` TEXT NOT NULL , PRIMARY KEY (`command_ID`)) ENGINE = InnoDB;");
        $commands->execute();

        $dispense = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_packages_dispensation` ( `dispense_ID` INT(16) NOT NULL AUTO_INCREMENT , `dispense_USERNAME` VARCHAR(16) NOT NULL , `dispense_SERVER` INT(11) NOT NULL , `dispense_COMMAND` TEXT NOT NULL , `dispense_DATE` DATE NOT NULL , `dispense_CLAIMEND` DATETIME NOT NULL , `dispense_ACTIVATED` INT(1) NOT NULL , PRIMARY KEY (`dispense_ID`)) ENGINE = InnoDB;");
        $dispense->execute();
    }
}