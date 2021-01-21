<?php

namespace app\api\admin;

use app\lib\Model;

class Chargeback extends Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function earnsBlocked()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_STATUS`=? OR `transaction_STATUS`=? OR `transaction_STATUS`=?");
        $stmt->execute([ 5, 'in_mediation', 'Reversed' ]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $sum = 0;

        foreach ($fetch as $rs)
        {
            $detail_id = $rs->transaction_DETAILS;

            $sum += $this->getAmountBlockedByDetailId($detail_id);
        }

        return $sum;
    }

    public function opens()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_STATUS`=? OR `transaction_STATUS`=? OR `transaction_STATUS`=?");
        $stmt->execute([ 5, 'in_mediation', 'Reversed' ]);

        return $stmt->rowCount();
    }

    public function log()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions` WHERE `transaction_STATUS`=? OR `transaction_STATUS`=? OR `transaction_STATUS`=?");
        $stmt->execute([ 5, 'in_mediation', 'Reversed' ]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result)
        {
            $detail = $this->fetchDetails($result->transaction_DETAILS);
            $amount = 'R$'.number_format($detail->datail_NET_AMOUNT, 2, ',', '.');

            $table .= "<tr>
                            <td><small>{$result->transaction_CODE}</small></td>
                            <td><small>{$result->transaction_USERNAME}</small></td>
                            <td>{$amount}</td>
                            <td><button class=\"btn btn-sm btn-block btn-dark\" data-toggle=\"modal\" data-target=\"#t-{$result->transaction_ID}\">ver</button></td>
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

            $p = new \app\api\site\Packages();
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

    private function fetchDetails($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_details` WHERE `detail_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject();
    }

    private function getAmountBlockedByDetailId($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_transactions_details` WHERE `detail_ID`=?");
        $stmt->execute([$id]);

        return $stmt->fetchObject()->datail_NET_AMOUNT;
    }

}