<?php

namespace app\api;

use app\api\admin\Block;
use app\lib\Model;

class Refunds extends Model
{

    private $statusToRefund = [
        5 => "Em disputa",
        6 => "Devolvido",
        'in_mediation' => 'Em disputa',
        'refunded' => 'Devolvido',
        'charged_back' => 'Estornado',
        'Refunded' => 'Devolvido',
        'Reversed' => 'Devolvido',
        'Processed' => 'Aprovado'
    ];

    private $block;

    public function __construct()
    {
        parent::__construct();

        $this->block = new Block();

        $this->table();
    }

    public function add($code, $username, $status)
    {
        if(!$this->verify($status))
        {
            return;
        }

        if(!$this->exists($code))
        {
            $sql = "INSERT INTO `website_refunds`(`refund_CODE`, `refund_USERNAME`) VALUES (?, ?)";
            $this->getConnection()->prepare($sql)->execute([$code, $username]);
        }

        $sql = "";

        $this->block->remote($username);
    }

    private function verify($status)
    {
        return array_key_exists($this->statusToRefund, $status);
    }

    private function exists($code)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_refunds` WHERE `refund_CODE`=?");
        $stmt->execute([$code]);
        return $stmt->rowCount() > 0;
    }

    private function table()
    {
        $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_refunds` ( `refund_ID` INT(11) NOT NULL AUTO_INCREMENT , `refund_CODE` VARCHAR(36) NOT NULL , `refund_USERNAME` VARCHAR(16) NOT NULL , PRIMARY KEY (`refund_ID`)) ENGINE = InnoDB;")->execute();
    }
}