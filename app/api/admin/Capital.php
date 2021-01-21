<?php

namespace app\api\admin;

use app\api\Transactions;
use app\lib\Forms;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Capital extends Model
{

    public function __construct()
    {
        parent::__construct();
        $this->tables();
    }

    public function addExpense()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'Requisição bloqueada' ]);
        }
        if(Forms::isEmpty($_POST, [ 'type', 'name', 'amount' ]))
        {
            return Json::encode(['response' => 'error', 'Informes os dados' ]);
        }

        $date = ($_POST['type'] == 1) ? '' : date("Y-m-d");

        $stmt = $this->getConnection()->prepare("INSERT INTO `website_capital_expenses`(`expense_TYPE`, `expense_NAME`, `expense_AMOUNT`, `expense_DATE`, `expense_TERM`) VALUES (?, ?, ?, ? ,?)");
        $stmt->execute([ $_POST['type'], $_POST['name'], $_POST['amount'], $date, $_POST['term'] ]);

        return Json::encode(['response' => 'ok', 'Regitrado com sucesso' ]);
    }

    public function addApportionment()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'Requisição bloqueada' ]);
        }
        if(Forms::isEmpty($_POST, [ 'name', 'percent' ]))
        {
            return Json::encode(['response' => 'error', 'Informes os dados' ]);
        }

        $stmt = $this->getConnection()->prepare("INSERT INTO `website_capital_apportionment`(`apportionment_USER`, `apportionment_PERCENT`) VALUES (?, ?)");
        $stmt->execute([ $_POST['name'], $_POST['percent'] ]);

        return Json::encode(['response' => 'ok', 'Cadastrado com sucesso' ]);
    }

    public function paidExpense()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'Requisição bloqueada' ]);
        }
        if(Forms::isEmpty($_POST, [ 'id' ]))
        {
            return Json::encode(['response' => 'error', 'ID não informado' ]);
        }
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_capital_logs`(`log_CAPITAL_ID`, `log_DATE`) VALUES (?, ?)");
        $stmt->execute([$_POST['id'], date("Y-m-d")]);

        return Json::encode(['response' => 'ok', 'Pago!' ]);
    }

    public function showRate()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM website_capital_apportionment");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $rs)
        {
            $transaction = new Transactions();

            $amount = $transaction->getEarnsInMonth() - $this->expensePaidAmount();
            $percent = $rs->apportionment_PERCENT;

            $receive = ($amount * $percent) / 100;
            $receive = 'R$'.number_format($receive, 2, ',', '.');

            $table .= "<tr>
                            <th scope=\"row\">{$rs->apportionment_ID}</th>
                            <td>{$rs->apportionment_USER}</td>
                            <td>{$rs->apportionment_PERCENT}<b>%</b></td>
                            <td>{$receive}</td>
                        </tr>";
        }
        return $table;
    }

    public function showExpenses()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_capital_expenses`");
        $stmt->execute();
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";
        foreach ($fetch as $result) {

            $amount = "R$".number_format($result->expense_AMOUNT, 2, ',', '.');
            $term   = (!empty($result->expense_TERM)) ? $result->expense_TERM."/".date("m/Y") : '-/-';
            $status = "Não pago";
            $btn = "";

            if(empty($result->expense_TERM))
            {
                $status = "Pago";
                $btn = "disabled";
            }else{
                if($this->hasPaid($result->expense_ID))
                {
                    $status = "Pago";
                    $btn = "disabled";
                }
            }

            if($result->expense_TYPE == 2)
            {
                $paid = date("d/m/Y", strtotime($result->expense_DATE));
            }else{
                if($this->hasPaid($result->expense_ID))
                {
                    $paid = date("d/m/Y", strtotime($this->whenPaid($result->expense_ID)));
                }else{
                    $paid = "-/-";
                }
            }

            $show = true;

            if($result->expense_TYPE == 2)
            {
                $exp = explode('-', $result->expense_DATE);
                $exp = $exp[0].'-'.$exp[1];
               if(!$exp == date("Y-m"))
               {
                   $show = false;
               }
            }

            if($show) {
                $table.="<tr>
                        <th scope=\"row\">{$result->expense_ID}</th>
                        <td>{$result->expense_NAME}</td>
                        <td>{$amount}</td>
                        <td>{$term}</td>
                        <td>{$paid}</td>
                        <td>{$status}</td>
                        <td><button class=\"btn btn-sm btn-block btn-success setpaid-expense\" id='{$result->expense_ID}' $btn>Pagar</button></td>
                    </tr>";
            }
        }
        return $table;
    }

    public function expensePaidAmount()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_capital_expenses`");
        $stmt->execute();

        $amount = 0;
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);

        foreach ($fetch as $result) {
            if($result->expense_TYPE == 1) {
                if($this->hasPaid($result->expense_ID))
                {
                    $amount += $result->expense_AMOUNT;
                }
            }else{
                if(date("Y-m") == date("Y-m", strtotime($result->expense_DATE)))
                {
                    $amount += $result->expense_AMOUNT;
                }
            }
        }

        return $amount;
    }

    public function expenseAmount()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_capital_expenses`");
        $stmt->execute();

        $amount = 0;
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);

        foreach ($fetch as $result) {
            if($result->expense_TYPE == 1) {
                if(!$this->hasPaid($result->expense_ID))
                {
                    $amount += $result->expense_AMOUNT;
                }
            }else{
                if(date("Y-m") == date("Y-m", strtotime($result->expense_DATE)))
                {
                    $amount += $result->expense_AMOUNT;
                }
            }
        }

        return $amount;
    }

    private function hasPaid($id)
    {
        $data = date("Y-m");
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_capital_logs` WHERE `log_CAPITAL_ID`={$id} AND `log_DATE` LIKE '%{$data}%'");
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function whenPaid($id)
    {
        $data = date("Y-m");
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_capital_logs` WHERE `log_CAPITAL_ID`={$id} AND `log_DATE` LIKE '%{$data}%'");
        $stmt->execute();
        return $stmt->fetchObject()->log_DATE;
    }

    private function tables()
    {
        $expenses = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_capital_expenses` ( `expense_ID` INT(11) NOT NULL AUTO_INCREMENT ,  `expense_TYPE` INT(1) NOT NULL ,  `expense_NAME` VARCHAR(50) NOT NULL ,  `expense_AMOUNT` DECIMAL(10,2) NOT NULL,  `expense_DATE` DATE NULL DEFAULT NULL ,  `expense_TERM` INT(3) NULL DEFAULT NULL ,    PRIMARY KEY  (`expense_ID`)) ENGINE = InnoDB;");
        $expenses->execute();

        $logs = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_capital_logs` ( `log_ID` INT(11) NOT NULL AUTO_INCREMENT , `log_CAPITAL_ID` INT(11) NOT NULL , `log_DATE` DATE NOT NULL , PRIMARY KEY (`log_ID`)) ENGINE = InnoDB;");
        $logs->execute();

        $apportionment = $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_capital_apportionment` ( `apportionment_ID` INT(11) NOT NULL AUTO_INCREMENT , `apportionment_USER` VARCHAR(50) NOT NULL , `apportionment_PERCENT` DECIMAL(10,2) NOT NULL , PRIMARY KEY (`apportionment_ID`)) ENGINE = InnoDB;");
        $apportionment->execute();
    }

}