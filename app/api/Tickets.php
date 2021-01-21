<?php

namespace app\api;

use app\api\site\Profile;
use app\lib\Forms;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Tickets extends Model
{

    private $profile;
    private $ticketSTRING = [
        1 => 'Aberto',
        2 => 'Respondido',
        3 => 'Fechado'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->profile = new Profile();
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
        if(!$forms->checkValid($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'CSFR detectado!']);
        }
        if(empty($_POST[$inputs['subject']]) || empty($_POST[$inputs['order']]) || empty($_POST[$inputs['body']]))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe todos os dados']);
        }
        if($this->userHasOpened($this->profile->username()))
        {
            return Json::encode(['response' => 'error', 'message' => 'Você já tem ticket(s) aberto(s)']);
        }

        $id = $this->create($this->profile->username(), $_POST[$inputs['subject']], $_POST[$inputs['order']]);
        $this->addMessage($id, $_POST[$inputs['body']], 1);

        return Json::encode(['response' => 'ok', 'message' => 'Ticket aberto']);
    }

    public function countOpeneds()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets` WHERE `ticket_STATUS`=?;");
        $stmt->execute([1]);
        return $stmt->rowCount();
    }

    public function show()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets` WHERE `ticket_STATUS`=?");
        $stmt->execute([1]);

        if($stmt->rowCount() == 0)
        {
            return "<tr>
                        <td colspan=\"5\" align=\"center\">Não há tickets abertos</td>
                    </tr>";
        }

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $rs)
        {
            $messages = $this->getMessages($rs->ticket_ID, false);
            $table .= "<tr>
                            <td>{$rs->ticket_ID}</td>
                            <td>{$rs->ticket_AUTHOR}</td>
                            <td>{$rs->ticket_ORDER}</td>
                            <td>{$rs->ticket_SUBJECT}</td>
                            <td>
                                <button class=\"btn btn-sm btn-primary\" data-toggle=\"modal\" data-target=\"#ticket-{$rs->ticket_ID}\"><i class=\"fa fa-eye\"></i></button>
                                <button class=\"btn btn-sm btn-danger ticket-close\" id='{$rs->ticket_ID}'><i class=\"fa fa-remove\"></i></button>
                            </td>
                        </tr>
                        <div class=\"modal fade\" id=\"ticket-{$rs->ticket_ID}\" tabindex=\"-1\" role=\"dialog\">
                          <div class=\"modal-dialog modal-lg\" role=\"document\">
                            <div class=\"modal-content\">
                              <div class=\"modal-header\">
                                <h5 class=\"modal-title\">Ticket #{$rs->ticket_ID}</h5>
                                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                  <span aria-hidden=\"true\">&times;</span>
                                </button>
                              </div>
                              <div class=\"modal-body p-4\"> 
                                <div class='row'>
                                    {$messages}
                                    <div class='col-12'>
                                        <select class='form-control autoreply' id='{$rs->ticket_ID}'>
                                            <option selected disabled>Selecione uma resposta automática</option>
                                            {$this->liMessage()}
                                        </select>
                                    </div>
                                    <br><br>
                                    <form class='replyTicket row'>
                                        <input type='hidden' name='id' value='{$rs->ticket_ID}'>
                                        <input type='hidden' name='agent' value='2'>
                                        <div class='col-md-10'><textarea class=\"form-control\" name='body' placeholder='Escreva a resposta'></textarea></div>
                                        <div class='col-md-2'><button type=\"submit\" class=\"btn btn-primary btn-block\">Enviar</button></div>
                                    </form>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>";
        }

        return $table;
    }

    public function logs()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets`");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $rs)
        {
            $messages = $this->getMessages($rs->ticket_ID, false);
            $table .= "<tr>
                            <td>{$rs->ticket_ID}</td>
                            <td>{$rs->ticket_AUTHOR}</td>
                            <td>{$rs->ticket_ORDER}</td>
                            <td>{$rs->ticket_SUBJECT}</td>
                            <td>{$this->ticketSTRING[$rs->ticket_STATUS]}</td>
                            <td>
                                <button class=\"btn btn-sm btn-primary\" data-toggle=\"modal\" data-target=\"#ticket-{$rs->ticket_ID}\">visualizar ticket</button>
                            </td>
                        </tr>
                        <div class=\"modal fade\" id=\"ticket-{$rs->ticket_ID}\" tabindex=\"-1\" role=\"dialog\">
                          <div class=\"modal-dialog modal-lg\" role=\"document\">
                            <div class=\"modal-content\">
                              <div class=\"modal-header\">
                                <h5 class=\"modal-title\">Ticket #{$rs->ticket_ID}</h5>
                                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                  <span aria-hidden=\"true\">&times;</span>
                                </button>
                              </div>
                              <div class=\"modal-body p-4\"> 
                                <div class='row'>
                                    {$messages}
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>";
        }

        return $table;
    }

    public function close()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST['id']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Não é possível fechar o ticket']);
        }

        $this->update($_POST['id'], 3);

        return Json::encode(['response' => 'ok', 'message' => 'Ticket fechado']);
    }

    public function reply()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST['body']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Não é possível responder em branco']);
        }

        if($_POST['agent'] == 1)
        {
            $status = 1;
        }else{
            $status = 2;
        }

        $this->addMessage($_POST['id'], $_POST['body'], $_POST['agent']);
        $this->update($_POST['id'], $status);

        return Json::encode(['response' => 'ok', 'message' => 'Ticket respondido']);
    }

    public function autoreply()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST['id']) || empty($_POST['reply']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Impossivel responder']);
        }

        $this->update($_POST['id'], 2);
        $this->addMessage($_POST['id'], $this->replyMessage($_POST['reply']), 2);

        return Json::encode(['response' => 'ok', 'message' => 'Ticket respondido']);
    }

    public function replyMessage($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets_toreply` WHERE `reply_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->reply_BODY;
    }

    public function showToUsername($username)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets` WHERE `ticket_AUTHOR`=?");
        $stmt->execute([$username]);

        if($stmt->rowCount() == 0)
        {
            return "<tr>
                        <td colspan=\"5\" align=\"center\">Não há tickets</td>
                    </tr>";
        }

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $rs)
        {
            $messages = $this->getMessages($rs->ticket_ID);
            $disabled = $this->userHasOpened($rs->ticket_AUTHOR) ? 'readonly disabled tabindex=\'-1\'' : '';
            $disabledMSG = $this->userHasOpened($rs->ticket_AUTHOR) ? 'Você ainda não pode responder' : 'Escreva sua resposta';
            $table .= "<tr>
                            <td>{$rs->ticket_ID}</td>
                            <td>{$rs->ticket_SUBJECT}</td>
                            <td>#{$rs->ticket_ORDER}</td>
                            <td>{$this->ticketSTRING[$rs->ticket_STATUS]}</td>
                            <td>
                                <button class=\"btn btn-sm btn-primary\" data-toggle=\"modal\" data-target=\"#ticket-{$rs->ticket_ID}\"><i class=\"ion-eye\"></i></button>
                                <button class=\"btn btn-sm btn-danger ticket-close\" id='{$rs->ticket_ID}'><i class=\"ion-close\"></i></button>
                            </td>
                        </tr>
                        <div class=\"modal fade\" id=\"ticket-{$rs->ticket_ID}\" tabindex=\"-1\" role=\"dialog\">
                          <div class=\"modal-dialog modal-lg\" role=\"document\">
                            <div class=\"modal-content\">
                              <div class=\"modal-header\">
                                <h5 class=\"modal-title\">Ticket #{$rs->ticket_ID}</h5>
                                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                  <span aria-hidden=\"true\">&times;</span>
                                </button>
                              </div>
                              <div class=\"modal-body p-4\">
                                <div class='row'>
                                    {$messages}
                                    <form class='replyTicket row'>
                                        <input type='hidden' name='id' value='{$rs->ticket_ID}'>
                                        <input type='hidden' name='agent' value='1'>
                                        <div class='col-md-10'><textarea class=\"form-control\" name='body' $disabled placeholder='{$disabledMSG}'></textarea></div>
                                        <div class='col-md-2'><button type=\"submit\" $disabled class=\"btn btn-primary btn-block\">Enviar</button></div>
                                    </form>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>";
        }

        return $table;
    }

    private function update($id, $status)
    {
        $stmt = $this->getConnection()->prepare("UPDATE `website_tickets` SET `ticket_STATUS`=? WHERE `ticket_ID`=?");
        $stmt->execute([$status, $id]);
    }

    private function getMessages($id, $agent = true)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets_messages` WHERE `message_TICKET`=?");
        $stmt->execute([$id]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $messages = "";

        foreach ($fetch as $result)
        {
            $date = date("d/m/Y à\s H:i", strtotime($result->message_DATE));
            if($agent)
            {
                if($result->message_AGENT == 1)
                {
                    $messages .= "<div class='col-md-4'></div>
                                    <div class='col-md-8'>
                                        <div class='card agent1'>
                                            <div class='card-body'>
                                                {$result->message_BODY} <br><br>
                                            </div>
                                        </div>
                                        <div class='date-ticket'>{$date}</div>
                                        <br><br>
                                    </div>";
                }else{
                    $messages .= "<div class='col-md-8'>
                                        <div class='card agent2'>
                                            <div class='card-body'>
                                                {$result->message_BODY} <br><br>
                                            </div>
                                        </div>
                                        <div class='date-ticket'>{$date}</div>
                                        <br><br>
                                    </div>
                                    <div class='col-md-4'></div>";
                }

            }else{
                if($result->message_AGENT == 2)
                {
                    $messages .= "<div class='col-md-4'></div>
                                    <div class='col-md-8'>
                                        <div class='card agent1'>
                                            <div class='card-body'>
                                                {$result->message_BODY}
                                            </div>
                                        </div>
                                        <div class='date-ticket'>{$date}</div>
                                        <br><br>
                                    </div>";
                }else{
                    $messages .= "<div class='col-md-8'>
                                        <div class='card agent2'>
                                            <div class='card-body'>
                                                {$result->message_BODY}
                                            </div>
                                        </div>
                                        <div class='date-ticket'>{$date}</div>
                                        <br><br>
                                    </div>
                                    <div class='col-md-4'></div>";
                }
            }
        }

        return $messages;
    }

    public function addAutoMessage()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST['name']) || empty($_POST['body']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Informe todos os campos']);
        }
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_tickets_toreply`(`reply_NAME`, `reply_BODY`) VALUES (?, ?)");
        $stmt->execute([$_POST['name'], nl2br($_POST['body'])]);

        return Json::encode(['response' => 'ok', 'message' => 'Adicionado com suceso']);
    }

    public function autoTable()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets_toreply`");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result)
        {
            $table .= "<tr>
                            <th scope=\"row\">{$result->reply_NAME}</th>
                            <td><button class=\"btn btn-primary\" data-toggle=\"modal\" data-target=\"#md-{$result->reply_ID}\">visualizar</button> <button class=\"btn btn-danger message-delete\" id=\"{$result->reply_ID}\">deletar</button></td>
                        </tr>
                        <div class=\"modal fade\" id=\"md-{$result->reply_ID}\" tabindex=\"-1\" role=\"dialog\"> 
                            <div class=\"modal-dialog\" role=\"document\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\">Mensagem</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                            <span aria-hidden=\"true\">&times;</span>
                                        </button>
                                    </div>
                                    <div class=\"modal-body\">
                                        {$result->reply_BODY}
                                    </div>
                                </div>
                            </div>
                        </div>";
        }

        return $table;
    }

    public function deleteMessage()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST['id']))
        {
            return Json::encode(['response' => 'error', 'message' => 'Impossível deletar']);
        }
        $stmt = $this->getConnection()->prepare("DELETE FROM `website_tickets_toreply` WHERE `reply_ID`=?");
        $stmt->execute([$_POST['id']]);

        return Json::encode(['response' => 'ok']);
    }

    public function liMessage()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets_toreply`");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $li = "";

        foreach ($fetch as $result)
        {
            $li .= "<option value='{$result->reply_ID}'>{$result->reply_NAME}</option>";
        }

        return $li;
    }

    private function userHasOpened($username)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_tickets` WHERE `ticket_AUTHOR`=? AND `ticket_STATUS`=?");
        $stmt->execute([$username, 1]);
        return $stmt->rowCount() > 0;
    }

    private function create($author, $subject, $order)
    {
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_tickets`(`ticket_AUTHOR`, `ticket_SUBJECT`, `ticket_ORDER`, `ticket_STATUS`, `ticket_AGENT`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$author, $subject, $order, 1, 1]);
        return $this->getConnection()->lastInsertId();
    }

    private function addMessage($id, $body, $agent)
    {
        $body = nl2br($body);
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_tickets_messages`(`message_TICKET`, `message_BODY`, `message_DATE`, `message_AGENT`) VALUES (?,?,?,?)");
        $stmt->execute([$id, $body, date("Y-m-d H:i:s"), $agent]);
    }

    private function tables()
    {
        $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_tickets` ( `ticket_ID` INT(11) NOT NULL AUTO_INCREMENT ,  `ticket_AUTHOR` VARCHAR(60) NOT NULL ,  `ticket_SUBJECT` VARCHAR(80) NOT NULL ,  `ticket_ORDER` INT(11) NOT NULL ,  `ticket_STATUS` INT(1) NOT NULL ,  `ticket_AGENT` VARCHAR(60) NOT NULL ,    PRIMARY KEY  (`ticket_ID`)) ENGINE = InnoDB;")->execute();
        $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_tickets_messages` ( `message_ID` INT(11) NOT NULL AUTO_INCREMENT , `message_TICKET` INT(11) NOT NULL , `message_BODY` TEXT NOT NULL , `message_DATE` DATETIME NOT NULL , `message_AGENT` VARCHAR(60) NOT NULL , PRIMARY KEY (`message_ID`)) ENGINE = InnoDB;")->execute();
        $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_tickets_toreply` ( `reply_ID` INT(11) NOT NULL AUTO_INCREMENT , `reply_NAME` VARCHAR(60) NOT NULL , `reply_BODY` TEXT NOT NULL , PRIMARY KEY (`reply_ID`)) ENGINE = InnoDB;")->execute();
    }
}