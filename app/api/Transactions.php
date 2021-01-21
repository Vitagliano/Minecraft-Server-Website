<?php

namespace app\api;

use app\api\admin\Servers;
use app\api\site\Packages;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Transactions extends Model
{

    private $approved = [
        3 => 'ok',
        4 => 'ok',
        'approved' => 'ok',
        'Completed' => 'ok'
    ];

    private $pending = [
        'pending' => 'ok',
        'in_process' => 'ok',
        'Pending' => 'ok',
        1 => 'ok',
        2 => 'ok',
    ];

    private $payment_type = [
        'account_money' => 'Saldo',
        'ticket' => 'Boleto',
        'bank_transfer' => 'Transaferência bancária',
        'atm' => 'ATM',
        'credit_card' => 'Cartão de crédito',
        'debit_card' => 'Cartão de débito',
        'prepaid_card' => 'Cartão pré-pago',
        1 => 'Cartão de crédito',
        2 => 'Boleto',
        3 => 'Débito online',
        4 => 'Saldo',
        5 => 'Oi Paggo',
        7 => 'Depósito em conta',
        'echeck' => 'eCheck',
        'instant' => 'Cartão de Crédito/Saldo'
    ];

    private $status = [
        1 => "Pendente",
        2 => "Em análise",
        3 => "Aprovado",
        4 => "Disponível",
        5 => "Em disputa",
        6 => "Devolvido",
        7 => "Cancelado",
        9 => "Devolvido",
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'in_process' => 'Em análise',
        'in_mediation' => 'Em disputa',
        'rejected' => 'Rejeitado',
        'cancelled' => 'Cancelado',
        'refunded' => 'Devolvido',
        'charged_back' => 'Estornado',
        'Canceled_Reversal' => 'Aprovado',
        'Completed' => 'Disponível',
        'Created' => 'Criado',
        'Denied' => 'Rejeitado',
        'Expired' => 'Expirado',
        'Failed' => 'Falhou',
        'Pending' => 'Pendente',
        'Refunded' => 'Devolvido',
        'Reversed' => 'Devolvido',
        'Processed' => 'Aprovado',
        'Voided' => 'Anulado'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->tables();
    }

    public function active()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST['package']))
        {
            return Json::encode(['response' => 'error', 'message' => 'ID não informado']);
        }

        $commands = $this->getCommands($_POST['package']);
        foreach ($commands as $command)
        {
            $this->addDispense($_POST['package'], $command->command_TYPE, $_POST['username'], $command->command_SERVER, str_replace('%p%', $_POST['username'], $command->command_TOSEND));
        }

        return Json::encode(['response' => 'ok', 'message' => 'Pacote enviado']);
    }

    public function approve()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST['id']))
        {
            return Json::encode(['response' => 'error', 'message' => 'ID não informado']);
        }
        $stmt = $this->getConnection()->prepare("UPDATE `website_transactions` SET `transaction_PAID`=? WHERE `transaction_ID`=?");
        $stmt->execute([ 1, $_POST['id'] ]);

        $this->dispense($this->getReference($_POST['id']));

        return Json::encode(['response' => 'ok', 'message' => 'Compra aprovada']);
    }

    private function dispense($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_cart` WHERE `cart_ID`=?");
        $stmt->execute([$id]);

        $fetch = $stmt->fetchObject();

        $username = $fetch->cart_USERNAME;
        $packages = explode(";", $fetch->cart_PACKAGES);

        foreach ($packages as $package)
        {
            $info = explode(":", $package);

            $package  = $info[0];
            $quantity = $info[2];

            $p = new Packages();
            $p->addSalePackage($package);

            for ($i = 1; $i <= $quantity; $i++)
            {
                $commands = $this->getCommands($package);
                foreach ($commands as $command)
                {
                    $this->addDispense($package, $command->command_TYPE, $username, $command->command_SERVER, str_replace('%p%', $username, $command->command_TOSEND));
                }
            }
        }
    }

    private function getCommands($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages_commands` WHERE `command_PACKAGE`=? AND `command_TYPE`!=?");
        $stmt->execute([$id, 3]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    private function addDispense($package, $type, $username, $server, $command)
    {
        $date = date("Y-m-d");

        if($type == 2) {
            if($this->getDuration($package) > 0)
            {
                $date = date("Y-m-d", strtotime(date("Y-m-d"). ' + '.$this->getDuration($package).' days'));
            }
        }

        $sql = "INSERT INTO `website_packages_dispensation`(`dispense_USERNAME`, `dispense_SERVER`, `dispense_COMMAND`, `dispense_DATE`) VALUES (?, ?, ?, ?)";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([ $username, $server, $command, $date ]);
    }

    private function getDuration($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages` WHERE `package_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->package_EXPIRE;
    }

    private function getReference($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->transaction_REFERENCE;
    }

    public function salesCount()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_PAID`=?");
        $stmt->execute([1]);
        return $stmt->rowCount();
    }

    public function getTotalEarns()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_notifications`;");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $sum = 0;

        foreach ($fetch as $result => $value)
        {
            if(array_key_exists($value->notification_STATUS, $this->approved)) {
                $detail_id = $this->getDetailID($value->notification_TRANSACTION_ID);
                $sum += $this->getTransactionAmount($detail_id);
            }
        }

        return $sum;
    }

    public function getWeekEarns()
    {

        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_notifications` WHERE `notification_DATE` BETWEEN CURRENT_DATE()-7 AND CURRENT_DATE();");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $sum = 0;

        foreach ($fetch as $result => $value)
        {
            if(array_key_exists($value->notification_STATUS, $this->approved)) {
                $detail_id = $this->getDetailID($value->notification_TRANSACTION_ID);
                $sum += $this->getTransactionAmount($detail_id);
            }
        }

        return $sum;
    }

    public function getEarnsInDate($date)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_notifications` WHERE `notification_DATE` = ?");
        $stmt->execute([$date]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $sum = 0;

        foreach ($fetch as $result => $value)
        {
            if(array_key_exists($value->notification_STATUS, $this->approved)) {
                $detail_id = $this->getDetailID($value->notification_TRANSACTION_ID);
                $sum += $this->getTransactionAmount($detail_id);
            }
        }

        return $sum;
    }

    public function getEarnsInMonth($type = 1)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_notifications` WHERE `notification_DATE` LIKE '%".date("Y-m")."%'");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $sum = 0;

        foreach ($fetch as $result => $value)
        {
            if(array_key_exists($value->notification_STATUS, $this->approved)) {
                if($type == 1)
                {
                    $detail_id = $this->getDetailID($value->notification_TRANSACTION_ID);
                    $sum += $this->getTransactionAmount($detail_id);
                }else{
                    $detail_id = $this->getDetailID($value->notification_TRANSACTION_ID);
                    $sum += $this->getTransactionAmount($detail_id, "datail_GROSS_AMOUNT");
                }
            }
        }

        return $sum;
    }

    public function save($code, $username, $cart, $status, $name, $email, $net, $gross, $method_type, $method_code)
    {
        if (array_key_exists($status . '', $this->approved)) {
            $paid = 1;
        }else{
            $paid = 0;
        }

        if(!$this->hasCode($code))
        {
            $detail = $this->addDetails($name, $email, $net, $gross, $method_type, $method_code);

            $stmt = $this->getConnection()->prepare("INSERT INTO `website_transactions`(`transaction_CODE`, `transaction_USERNAME`, `transaction_REFERENCE`, `transaction_DETAILS`, `transaction_STATUS`, `transaction_PAID`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $username, $cart, $detail, $status, $paid]);
        }else{
            if($this->hasPaid($code)) {
                $paid = 1;
            }

            $stmt = $this->getConnection()->prepare("UPDATE `website_transactions` SET `transaction_STATUS`=?,`transaction_PAID`=? WHERE `transaction_CODE`=?");
            $stmt->execute([ $status, $paid, $code]);
        }
        $this->addLog($code, $status);
    }

    public function hasPaid($code)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_CODE`=?");
        $stmt->execute([$code]);
        return $stmt->fetchObject()->transaction_PAID == 1;
    }

    private function addDetails($name, $email, $net, $gross, $method_type, $method_code)
    {
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_transactions_details`(`datail_NAME`, `datail_EMAIL`, `datail_NET_AMOUNT`, `datail_GROSS_AMOUNT`, `datail_METHOD_TYPE`, `datail_METHOD_CODE`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $net, $gross, $method_type, $method_code]);
        return $this->getConnection()->lastInsertId();
    }

    private function hasCode($code)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_CODE`=?");
        $stmt->execute([$code]);
        return $stmt->rowCount() > 0;
    }

    private function addLog($code, $status)
    {
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_transactions_notifications`(`notification_TRANSACTION_ID`, `notification_DATE`, `notification_DATETIME`, `notification_STATUS`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->id($code), date("Y-m-d"), date("Y-m-d H:i:s"), $status]);
    }

    public function id($code)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_CODE`=?");
        $stmt->execute([$code]);
        return $stmt->fetchObject()->transaction_ID;
    }

    private function getDetailID($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_DETAILS`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->transaction_DETAILS;
    }

    private function getTransactionAmount($id, $type = "datail_NET_AMOUNT")
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_details` WHERE `detail_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->$type;
    }

    public function getDateInDays($days, $operation = "-")
    {
        $now = date("Y-m-d");
        $dates = [];

        for ($i = 0; $i < $days; $i++)
        {
            array_push($dates, date('Y-m-d', strtotime($now . "{$operation}{$i} days")));
        }

        return $dates;
    }

    public function showToUsername($username)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_USERNAME`=? AND `transaction_PAID`=? ORDER BY `transaction_ID` DESC");
        $stmt->execute([$username, 1]);
        if($stmt->rowCount() == 0)
        {
            return "<h5>Você ainda não teve nenhuma compra aprovada</h5>";
        }
        $html = "<div class=\"accordion\" id=\"purchases\">";

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);

        foreach ($fetch as $rs)
        {
            $cart = $this->fetchCart($rs->transaction_REFERENCE);
            $createdIn = new \DateTime($cart->cart_CREATED_IN);
            $createdIn = $createdIn->format("d/m/Y H:i");

            $detail = $this->fetchDetails($rs->transaction_DETAILS);
            $amount = 'R$'.number_format($detail->datail_GROSS_AMOUNT, 2, ',', '.');

            $approvedIn = $this->getApproved($rs->transaction_ID);

            $html.= "<div class=\"card\">
                        <div class=\"card-header\">
                            <a href=\"#\" data-toggle=\"collapse\" data-target=\"#purschase-{$rs->transaction_ID}\">
                                <div class=\"row\">
                                    <div class=\"col-md-7\">
                                        Compra #{$rs->transaction_REFERENCE}
                                    </div>
                                    <div class=\"col-md-5\">
                                        Aprovado em {$approvedIn}
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div id=\"purschase-{$rs->transaction_ID}\" class=\"collapse\" data-parent=\"#purchases\">
                            <div class=\"card-body\">
                                <div class=\"row\">
                                    <div class=\"col-md-6\">
                                        <b>Código de transação:</b> <br> <small>{$rs->transaction_CODE}</small> <br>
                                        <b>Compra iniciado em:</b> {$createdIn} <br>
                                        <b>Aprovada em:</b> {$approvedIn} <br>
                                        <b>IP:</b> {$cart->cart_IP} <br>
                                        <b>Código de referência:</b> {$rs->transaction_REFERENCE} <br>
                                    </div>
                                    <div class=\"col-md-6\"> 
                                        <b>Carrinho:</b> <a href=\"#\" data-toggle=\"modal\" data-target=\"#t-{$rs->transaction_ID}\">abrir</a><br>
                                        <b>Valor pago:</b> {$amount} <br>
                                        <b>Ativo em:</b> {$approvedIn} <br>
                                    </div>
                                    <div class=\"modal fade\" id=\"t-{$rs->transaction_ID}\" tabindex=\"-1\" role=\"dialog\">
                                      <div class=\"modal-dialog modal-lg\" role=\"document\">
                                        <div class=\"modal-content\">
                                          <div class=\"modal-header\">
                                            <h5 class=\"modal-title\">Carrinho</h5>
                                            <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                              <span aria-hidden=\"true\">&times;</span>
                                            </button>
                                          </div>
                                          <div class=\"modal-body\">
                                            <div class='row'>
                                                <div class='col-md-3'><b>Produto</b></div>
                                                <div class='col-md-3 text-center'><b>Quantia</b></div>
                                                <div class='col-md-3 text-center'><b>Valor</b></div>
                                                <div class='col-md-3 text-center'><b>Subtotal</b></div>
                                            </div>
                                            {$this->tableCart($rs->transaction_REFERENCE)}
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>";
        }

        $html.= "</div>";
        return $html;
    }

    public function log()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions`");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result)
        {
            $detail = $this->fetchDetails($result->transaction_DETAILS);
            $gross = 'R$'.number_format($detail->datail_GROSS_AMOUNT, 2, ',', '.');
            $amount = 'R$'.number_format($detail->datail_NET_AMOUNT, 2, ',', '.');

            $disable = "";
            if($result->transaction_PAID == 1)
            {
                $disable = " disabled tabindex='-1'";
            }

            $table .= "<tr>
                            <th scope=\"row\">{$result->transaction_ID}</th>
                            <td><small>{$result->transaction_CODE}</small></td>
                            <td>{$result->transaction_USERNAME}</td>
                            <td>{$result->transaction_REFERENCE}</td>
                            <td>{$gross}</td>
                            <td>{$amount}</td>
                            <td>{$this->status[$result->transaction_STATUS]}</td>
                            <td><button class=\"btn btn-sm btn-block btn-dark\" data-toggle=\"modal\" data-target=\"#t-{$result->transaction_ID}\">ver</button></td>
                            <td><button class=\"btn btn-sm btn-block btn-success approve-purchase\" id='{$result->transaction_ID}' $disable>Aprovar</button></td>
                            <td><button class=\"btn btn-sm btn-block btn-primary\" onclick=\"location.href='/admin/loja/report/{$result->transaction_ID}'\">ver</button></td>
                        </tr>
                        <div class=\"modal fade\" id=\"t-{$result->transaction_ID}\" tabindex=\"-1\" role=\"dialog\">
                          <div class=\"modal-dialog modal-lg\" role=\"document\">
                            <div class=\"modal-content\">
                              <div class=\"modal-header\">
                                <h5 class=\"modal-title\">Carrinho</h5>
                                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                  <span aria-hidden=\"true\">&times;</span>
                                </button>
                              </div>
                              <div class=\"modal-body\">
                                <div class='row'>
                                    <div class='col-md-3'><b>Produto</b></div>
                                    <div class='col-md-3 text-center'><b>Quantia</b></div>
                                    <div class='col-md-3 text-center'><b>Valor</b></div>
                                    <div class='col-md-3 text-center'><b>Subtotal</b></div>
                                </div>
                                {$this->tableCart($result->transaction_REFERENCE)}
                              </div>
                            </div>
                          </div>
                        </div>";
        }

        return $table;
    }

    private function tableCart($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_cart` WHERE `cart_ID`=?");
        $stmt->execute([$id]);

        $table = "";
        $fetch = $stmt->fetchObject();
        $packages = explode(";", $fetch->cart_PACKAGES);

        foreach ($packages as $package)
        {
            $info = explode(":", $package);

            $package  = $info[0];
            $server   = $info[1];
            $quantity = $info[2];

            $p = new Packages();
            $i = $p->info($package);

            $s = new Servers();

            $amount = 'R$'.number_format($i->amount, 2, ',', '.');
            $total  = 'R$'.number_format($i->amount * $quantity, 2, ',', '.');

            $table .= "<div class='row'>
                          <div class='col-md-3'>Pacote {$i->name} - {$s->name($server)}</div>
                          <div class='col-md-3 text-center'>{$quantity}</div>
                          <div class='col-md-3 text-center'>{$amount}</div>
                          <div class='col-md-3 text-center'>{$total}</div>
                        </div>";
        }

        return $table;
    }

    private function tableCart2($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_cart` WHERE `cart_ID`=?");
        $stmt->execute([$id]);

        $table = "";
        $fetch = $stmt->fetchObject();
        $packages = explode(";", $fetch->cart_PACKAGES);

        foreach ($packages as $package)
        {
            $info = explode(":", $package);

            $package  = $info[0];
            $server   = $info[1];
            $quantity = $info[2];

            $p = new Packages();
            $i = $p->info($package);

            $s = new Servers();

            $amount = 'R$'.number_format($i->amount, 2, ',', '.');
            $total  = 'R$'.number_format($i->amount * $quantity, 2, ',', '.');

            $table .= "<tr>
                        <td scope=\"row\">Pacote {$i->name}</td>
                        <td>{$s->name($server)}</td>
                        <td>{$quantity}</td>
                        <td>{$amount}</td>
                        <td>{$total}</td>
                    </tr>";
        }

        return $table;
    }

    public function hasPendingToUser($username)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_USERNAME`=?");
        $stmt->execute([$username]);
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($fetch as $result)
        {
            if(array_key_exists($result->transaction_STATUS, $this->pending))
                return true;
        }
        return false;
    }

    public function showHistoryToUser($username)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_USERNAME`=? ORDER BY `transaction_ID` DESC");
        $stmt->execute([$username]);

        if($stmt->rowCount() == 0)
        {
            return "<tr>
                        <td colspan=\"5\" align=\"center\">Não há pedidos</td>
                    </tr>";
        }

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result)
        {
            $cart = $this->fetchCart($result->transaction_REFERENCE);
            $createdIn = new \DateTime($cart->cart_CREATED_IN);
            $createdIn = $createdIn->format("d/m/Y H:i");

            $detail = $this->fetchDetails($result->transaction_DETAILS);
            $amount = 'R$'.number_format($detail->datail_GROSS_AMOUNT, 2, ',', '.');

            $table.="<tr>
                        <th scope=\"row\">{$result->transaction_REFERENCE}</th>
                        <td>{$createdIn}</td>
                        <td>{$amount}</td>
                        <td>{$this->status[$result->transaction_STATUS]}</td>
                    </tr>";
        }

        return $table;
    }

    private function fetchCart($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_cart` WHERE cart_ID=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject();
    }

    private function fetchDetails($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_details` WHERE `detail_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject();
    }

    private function getApproved($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_notifications` WHERE `notification_TRANSACTION_ID`=?");
        $stmt->execute([$id]);
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($fetch as $rs)
        {
            if(array_key_exists($rs->notification_STATUS, $this->approved))
            {
                $data = new \DateTime($rs->notification_DATETIME);
                return $data->format("d/m/Y à\s H:i");
            }
        }
        return "undefined";
    }

    public function liOrderToUsername($username)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_USERNAME`=?");
        $stmt->execute([$username]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $li = "";

        foreach ($fetch as $result)
        {

            $li .= "<option value='{$result->transaction_ID}'>Pedido Nº #{$result->transaction_REFERENCE}</option>";
        }

        return $li;
    }

    public function tableNotificationsID($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_notifications` WHERE `notification_TRANSACTION_ID`=?");
        $stmt->execute([$id]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result)
        {
            $table .= "<tr>
                        <td><small>{$this->getTransactionCodeByID($result->notification_TRANSACTION_ID)}</small></td>
                        <td>{$result->notification_DATETIME}</td>
                        <td>{$this->status[$result->notification_STATUS]}</td>
                    </tr>";
        }

        return $table;
    }

    public function getTransactionCodeByID($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->transaction_CODE;
    }

    public function listInfo($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_ID`=?");
        $stmt->execute([$id]);

        $fetch = $stmt->fetchObject();

        $cart = $this->fetchCart($fetch->transaction_REFERENCE);
        $createdIn = new \DateTime($cart->cart_CREATED_IN);
        $createdIn = $createdIn->format("d/m/Y à\s H:i");
        $detail = $this->fetchDetails($fetch->transaction_DETAILS);

        return [
            'name' =>  $detail->datail_NAME,
            'email' => $detail->datail_EMAIL,
            'gross' => $detail->datail_GROSS_AMOUNT,
            'net' => $detail->datail_NET_AMOUNT,
            'method' => $this->payment_type[$detail->datail_METHOD_TYPE],
            'fee' => $detail->datail_GROSS_AMOUNT - $detail->datail_NET_AMOUNT,
            'reference' => $fetch->transaction_REFERENCE,
            'date' => $createdIn,
            'products' => $this->tableCart2($fetch->transaction_REFERENCE)
        ];
    }

    public function todayTable()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` ORDER BY `transaction_ID` DESC LIMIT 10");
        $stmt->execute();

        if($stmt->rowCount() == 0)
        {
            return "<tr>
                        <td colspan='5'>Não há transações</td>
                    </tr>";
        }

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result)
        {
            $cart = $this->fetchCart($result->transaction_REFERENCE);
            $createdIn = new \DateTime($cart->cart_CREATED_IN);
            $createdIn = $createdIn->format("d/m/Y H:i");

            $detail = $this->fetchDetails($result->transaction_DETAILS);
            $amount = 'R$'.number_format($detail->datail_GROSS_AMOUNT, 2, ',', '.');

            $table .= "<tr>
                        <td>{$result->transaction_ID}</td>
                        <td>{$result->transaction_USERNAME}</td>
                        <td>{$createdIn}</td>
                        <td>{$amount}</td>
                        <td>{$this->status[$result->transaction_STATUS]}</td>
                    </tr>";
        }

        return $table;
    }

    public function getTransactionsDetailsToUsername($username)
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM `website_transactions` WHERE `transaction_USERNAME`=?');
        $stmt->execute([$username]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $result = [];

        foreach ($fetch as $res)
        {
            array_push($result, $res->transaction_DETAILS);
        }

        return $result;
    }

    public function getEmailOfTransaction($detail_id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_details` WHERE `detail_ID`=?");
        $stmt->execute([$detail_id]);
        return $stmt->fetchObject()->datail_EMAIL;
    }

    private function tables()
    {
        $transactions = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_transactions` ( `transaction_ID` INT(11) NOT NULL AUTO_INCREMENT , `transaction_CODE` VARCHAR(36) NOT NULL , `transaction_USERNAME` VARCHAR(16) NOT NULL , `transaction_REFERENCE` INT(11) NOT NULL , `transaction_DETAILS` INT(11) NOT NULL , `transaction_STATUS` VARCHAR(30) NOT NULL , `transaction_PAID` INT(1) NOT NULL DEFAULT '0' , PRIMARY KEY (`transaction_ID`)) ENGINE = InnoDB;");
        $transactions->execute();

        $details = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_transactions_details` ( `detail_ID` INT(11) NOT NULL AUTO_INCREMENT ,  `datail_NAME` VARCHAR(50) NOT NULL ,  `datail_EMAIL` VARCHAR(80) NOT NULL ,  `datail_NET_AMOUNT` DECIMAL(10,2) NOT NULL ,  `datail_GROSS_AMOUNT` DECIMAL(10,2) NOT NULL ,  `datail_METHOD_TYPE` INT(1) NOT NULL ,  `datail_METHOD_CODE` INT(4) NOT NULL ,    PRIMARY KEY  (`detail_ID`)) ENGINE = InnoDB;");
        $details->execute();

        $notifications = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_transactions_notifications` ( `notification_ID` INT(11) NOT NULL AUTO_INCREMENT ,  `notification_TRANSACTION_ID` INT(11) NOT NULL ,  `notification_DATE` DATE NOT NULL ,  `notification_DATETIME` DATETIME NOT NULL ,  `notification_STATUS` VARCHAR(30) NOT NULL ,    PRIMARY KEY  (`notification_ID`)) ENGINE = InnoDB;");
        $notifications->execute();
    }

}