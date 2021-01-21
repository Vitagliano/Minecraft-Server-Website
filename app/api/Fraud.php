<?php

namespace app\api;


use app\api\admin\Block;
use app\lib\Model;

class Fraud extends Model
{

    private $transactions, $block;

    public function __construct()
    {
        parent::__construct();

        $this->transactions = new Transactions();
        $this->block = new Block();

        $this->table();
    }

    public function add($username)
    {
        if($this->getTry($username) <= 3)
        {
            return;
        }
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_frauds`(`fraud_USERNAME`, `fraud_TRY`) VALUES(?, ?)");
        $stmt->execute([ $username, $this->getTry($username) ]);

        $this->block->remote($username);
    }

    private function getTry($username)
    {
        $details_id = $this->transactions->getTransactionsDetailsToUsername($username);

        $emails = [];

        foreach ($details_id as $item => $id) {
            array_push($emails, $this->transactions->getEmailOfTransaction($id));
        }

        array_unique($emails, SORT_REGULAR);

        return count($emails);
    }

    public function show()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_frauds`");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $res)
        {
            $table .= "<tr>
                          <th scope=\"row\">{$res->fraud_ID}</th>
                          <td>{$res->fraud_USERNAME}</td>
                          <td>{$res->fraud_TRY}</td>
                        </tr>";
        }

        return $table;
    }

    private function table()
    {
        $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_frauds` ( `fraud_ID` INT(11) NOT NULL AUTO_INCREMENT , `fraud_USERNAME` VARCHAR(16) NOT NULL , `fraud_TRY` INT(2) NOT NULL , PRIMARY KEY (`fraud_ID`)) ENGINE = InnoDB;")->execute();
    }

}