<?php

namespace app\api\admin;

use app\lib\Forms;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Accounts
{

    private $model, $settings;

    public function __construct()
    {
        $this->model = new Model();
        $this->settings = new Settings();
        $this->tables();
    }

    public function add($inputs)
    {
        $forms = new Forms();
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        if(empty($_POST[$inputs['username']]) || empty($_POST[$inputs['password']] || empty($_POST['permissions'])))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe todos os dados']);
        }
        if(!$forms->checkValid($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'CSFR detectado!']);
        }
        if($this->has($_POST[$inputs['username']]))
        {
            return Json::encode(['response' => 'error', 'message' => 'Usuário já cadastrado']);
        }

        $permissiosn = "";

        foreach ($_POST['permissions'] as $permission)
        {
            $permissiosn.=$permission." ";
        }

        $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_accounts`(`account_USERNAME`, `account_PASSWORD`, `account_PERMISSIONS`) VALUES (?,?,?)");
        $stmt->execute([$_POST[$inputs['username']], md5($_POST[$inputs['password']]), $permissiosn]);

        return Json::encode(['response' => 'ok', 'message' => 'Usuário cadastrado!']);
    }

    public function auth($inputs)
    {
        $forms = new Forms();
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        if(empty($_POST[$inputs['username']]) || empty($_POST[$inputs['password']]))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe todos os dados']);
        }
        if(!$forms->checkValid($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'CSFR detectado!']);
        }

        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts` WHERE `account_USERNAME`=? AND `account_PASSWORD`=?");
        $stmt->execute([ $_POST[$inputs['username']], md5($_POST[$inputs['password']]) ]);

        if($stmt->rowCount() == 0)
        {
            return Json::encode(['response' => 'error', 'message' => 'Acesso negado!']);
        }

        $mode = (isset($_POST[$inputs['mode']])) ? true : false;

        $this->setSession('WebAdminUser', $_POST[$inputs['username']], $mode);
        $this->addLog($this->id($_POST[$inputs['username']]), $_POST[$inputs['ip']]);

        return Json::encode(['response' => 'ok', 'message' => 'Autenticado com sucesso!']);
    }

    public function has($username)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts` WHERE `account_USERNAME`=?");
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }



    public function countnLogin()
    {
        $this->model->set($this->settings->printDatabases('nLogin')->host,
            $this->settings->printDatabases('nLogin')->username,
            $this->settings->printDatabases('nLogin')->password,
            $this->settings->printDatabases('nLogin')->database);
        $stmt = $this->model->getNewConnection()->prepare("SELECT * FROM `nLogin` ");
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function logged()
    {
        return (isset($_SESSION['WebAdminUser'])) ? true : (isset($_COOKIE['WebAdminUser'])) ? true : false;
    }

    public function logout()
    {
        if(isset($_SESSION['WebAdminUser']))
        {
            unset($_SESSION['WebAdminUser']);
            return;
        }
        unset($_COOKIE['WebAdminUser']);
        setcookie('WebAdminUser', '', time() - 3600, '/');
        return;
    }

    public function username()
    {
        if(isset($_SESSION['WebAdminUser']))
        {
            return $_SESSION['WebAdminUser'];
        }
        return $_COOKIE['WebAdminUser'];
    }

    private function setSession($name, $value, $mode = false)
    {
        if($mode)
        {
            return setcookie($name, $value, time()+3600*24*30, "/");
        }
        return $_SESSION[$name] = $value;
    }

    public function users()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts`");
        $stmt->execute();
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";
        foreach ($fetch as $result)
        {
            $table .= "<tr>
                        <td>{$result->account_USERNAME}</td>
                        <td>{$this->lastLogin($result->account_ID)}</td>
                        <td>
                            <center>
                                <button class=\"btn btn-sm btn-danger user-delete\" id='{$result->account_ID}'><i class=\"fa fa-remove\"></i></button>
                                <button class='btn btn-sm btn-warning' data-toggle=\"modal\" data-target=\"#perm-{$result->account_ID}\"><i class=\"fa fa-edit\"></i></button>
                            </center>
                        </td>
                        <div class=\"modal fade\" id=\"perm-{$result->account_ID}\" tabindex=\"-1\" role=\"dialog\">
                            <div class=\"modal-dialog modal-lg\" role=\"document\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\">Editar</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                            <span aria-hidden=\"true\">&times;</span>
                                        </button>
                                    </div>
                                    <div class=\"modal-body\">
                                        <form method=\"post\" class=\"editPerm\">
                                            <input type=\"hidden\" name=\"id\" value=\"{$result->account_ID}\">
                                            <div class=\"row\">
                                                <div class=\"col-md-3\">
                                                    <p><b>Minha loja</b></p>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.*\"> Todos privilégios</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.pacotes\"> Pacotes</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.servidores\"> Servidores</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.descontos\"> Descontos</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.estornos\"> Estornos</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.fraude\"> Anti-fraude</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.capital\"> Capital de Giro</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.ativar\"> Ativar produto</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.bloquear\"> Bloquear conta</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"store.transacoes\"> Transações</label>
                                                </div>
                                                <div class=\"col-md-3\">
                                                    <p><b>Site</b></p>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"site.*\"> Todos privilégios</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"site.postagens\"> Postagens</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"site.equipe\"> Equipe</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"site.atualizacoes\"> Changelog</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"site.emails\"> E-mails</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"site.termos\"> Termos de uso</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"site.manutencao\"> Manutenção</label>
                                                </div>
                                                <div class=\"col-md-3\">
                                                    <p><b>Suporte</b></p>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"support.*\"> Todos privilégios</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"support.abertos\"> Abertos</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"support.logs\"> Logs</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"support.mensagens\"> Mensagens prontas</label>
                                                </div>
                                                <div class=\"col-md-3\">
                                                    <p><b>Configurações</b></p>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"settings.*\"> Todos privilégios</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"settings.gateways\"> Formas de pagamento</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"settings.database\"> Banco de dados</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"settings.backup\"> Backup</label> <br>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"settings.usuarios\"> Adicionar usuários</label> <br>
                                                    <br>
                                                    <p><b>Dashboard</b></p>
                                                    <label><input type=\"checkbox\" name=\"permissions[]\" value=\"dashboard.*\"> Mostrar dashboard</label> <br>
                                                </div>
                                            </div>
                                            <br>
                                            <button class='btn btn-primary'>Salvar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </tr>";
        }
        return $table;
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

        $permissiosn = "";

        foreach ($_POST['permissions'] as $permission)
        {
            $permissiosn.=$permission." ";
        }

        $stmt = $this->model->getConnection()->prepare("UPDATE `website_accounts` SET `account_PERMISSIONS`=? WHERE `account_ID`=?");
        $stmt->execute([$permissiosn, $_POST['id']]);

        return Json::encode(['response' => 'ok', 'message' => 'Usuário editado!']);
    }

    public function logs()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts_logs`");
        $stmt->execute();
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";
        foreach ($fetch as $result)
        {
            if($this->hasID($result->log_ACCOUNT))
            {
                $date = date("d/m/Y H:i", strtotime($result->log_DATE));
                $table .= "<tr>
                                <td>{$result->log_ID}</td>
                                <td>{$this->userByID($result->log_ACCOUNT)}</td>
                                <td>{$date}</td>
                                <td>{$result->log_IP}</td>
                            </tr>";
            }
        }

        return $table;
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
        $stmt = $this->model->getConnection()->prepare("DELETE FROM `website_accounts` WHERE `account_ID`=?");
        $stmt->execute([$_POST['id']]);

        return Json::encode([ 'response' => 'ok' ]);
    }

    private function addLog($id, $ip)
    {
        $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_accounts_logs`(`log_ACCOUNT`, `log_DATE`, `log_IP`) VALUES (?, ?, ?)");
        $stmt->execute([$id, date("Y-m-d H:i:s"), $ip]);
    }

    private function lastLogin($id)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts_logs` WHERE `log_ACCOUNT`=? ORDER BY `log_ID` DESC LIMIT 1");
        $stmt->execute([$id]);
        if($stmt->rowCount() == 0)
        {
            return "não logou";
        }
        return date("d/m/Y H:i", strtotime($stmt->fetchObject()->log_DATE));
    }

    private function userByID($id)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts` WHERE `account_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->account_USERNAME;
    }

    private function id($username)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts` WHERE `account_USERNAME`=?");
        $stmt->execute([$username]);
        return $stmt->fetchObject()->account_ID;
    }

    private function row()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts`");
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function hasID($id)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_accounts` WHERE `account_ID`=?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    private function tables()
    {
        $accounts = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_accounts` ( `account_ID` INT(11) NOT NULL AUTO_INCREMENT , `account_USERNAME` VARCHAR(50) NOT NULL , `account_PASSWORD` VARCHAR(32) NOT NULL , `account_PERMISSIONS` TEXT NOT NULL , PRIMARY KEY (`account_ID`)) ENGINE = InnoDB;");
        $accounts->execute();
        if($this->row() == 0) {
            $setAccount = $this->model->getConnection()->prepare("INSERT INTO `website_accounts`(`account_USERNAME`, `account_PASSWORD`, `account_PERMISSIONS`) VALUES (?,?,?)");
            $setAccount->execute([ "admin", md5("12345678"), "*" ]);
        }
        $logs = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS  `website_accounts_logs` ( `log_ID` INT(11) NOT NULL AUTO_INCREMENT , `log_ACCOUNT` INT(11) NOT NULL , `log_DATE` DATETIME NOT NULL , `log_IP` VARCHAR(15) NOT NULL , PRIMARY KEY (`log_ID`)) ENGINE = InnoDB;");
        $logs->execute();
    }

}