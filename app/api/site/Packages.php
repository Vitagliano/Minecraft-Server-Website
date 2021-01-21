<?php

namespace app\api\site;

use app\api\admin\Discounts;
use app\lib\Model;

class Packages extends Model
{

    private $discounts, $server;

    public function __construct()
    {
        parent::__construct();
        $this->discounts = new Discounts();
        $this->server = new \app\api\admin\Servers();
        $this->tables();
    }

    public function category($s)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages` WHERE `package_SERVER`=?");
        $stmt->execute([$s]);
        if ($stmt->rowCount() == 0) {
            return "<div class='col-md-12'><h5 class='text-center text-muted' style='margin-top: 5vh'>Não há produtos</h5></div>";
        }
        $packages = "";
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($fetch as $rs) {
            $amount = 'R$' . number_format($rs->package_AMOUNT, 2, ',', '.');
            if ($this->discounts->hasGlobal($rs->package_SERVER)) {
                $amount = '<small style="color: black"><strike>' . $amount . '</strike></small><br> R$' . number_format($this->discounts->setGlobal($rs->package_SERVER, $rs->package_AMOUNT), 2, ',', '.');
            }
            if(strtotime($rs->package_DATE) <= strtotime(date("Y-m-d"))) {
                $packages .= "<div class=\"col-md-4 mb-3\">
                            <div class=\"package\">
                                <img src=\"{$rs->package_IMAGE}\" class=\"img-fluid\">
                                <div class=\"title\">{$rs->package_NAME}</div>
                                <div class=\"price\">{$amount}</div>
                                <button data-toggle=\"modal\" data-target=\"#modal-{$rs->package_ID}\">comprar</button>
                            </div>
                            <div class=\"modal fade\" id=\"modal-{$rs->package_ID}\" tabindex=\"-1\" role=\"dialog\">
                                <div class=\"modal-dialog\" role=\"document\">
                                    <div class=\"modal-content\">
                                        <div class=\"modal-header\">
                                            <h5 class=\"modal-title\">Adicionar ao carrinho</h5>
                                            <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>
                                        </div>
                                        <div class=\"modal-body\">
                                            <div class=\"row\">
                                                <div class=\"col-md-4\">
                                                    <img src=\"{$rs->package_IMAGE}\" class=\"img-fluid\">
                                                </div>
                                                <div class=\"col-md-8\">
                                                    <p class=\"m-0\">
                                                        <br>
                                                        <b>{$rs->package_NAME}</b>
                                                        <br>
                                                        Servidor: {$this->server->name($rs->package_SERVER)}
                                                        <br>
                                                        Valor: {$amount}
                                                    </p>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6>Descrição:</h6>
                                            <p>
                                                {$rs->package_DESCRIPTION}
                                            </p>
                                        </div>
                                        <div class=\"modal-footer\">
                                            <button type=\"button\" class=\"btn btn-dark\" data-dismiss=\"modal\">fechar</button>
                                            <button type=\"button\" class=\"btn btn-cart add-to-cart\" id='{$rs->package_ID}-{$rs->package_SERVER}'><i class=\"ion-ios-cart mr-1\"></i> adicionar no carrinho</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>";
            }
        }
        return $packages;
    }

    public function getMostPackageSale()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_sales_packages` WHERE `sale_MONTH`=? ORDER BY `sale_COUNT` DESC LIMIT 1");
        $stmt->execute([ date("m/Y") ]);

        if($stmt->rowCount() == 0)
        {
            return "";
        }

        $id = $stmt->fetchObject()->sale_PACKAGE;

        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages` WHERE `package_ID`=?");
        $stmt->execute([$id]);

        $result = $stmt->fetchObject();

        $amount = 'R$' . number_format($result->package_AMOUNT, 2, ',', '.');
        if ($this->discounts->hasGlobal($result->package_SERVER)) {
            $amount = '<small style="color: black"><strike>' . $amount . '</strike></small><br> R$' . number_format($this->discounts->setGlobal($result->package_SERVER, $result->package_AMOUNT), 2, ',', '.');
        }

        return "<br><div class=\"package\">
                        <h5 class='text-center'><small><b>Mais vendido</b></small></h5>
                        <img src=\"{$result->package_IMAGE}\" class=\"img-fluid\">
                        <div class=\"title\">{$result->package_NAME}</div>
                        <div class=\"price\">{$amount}</div>
                        <small class='text-muted'>({$this->server->name($result->package_SERVER)})</small>
                        <button class='add-to-cart' id='{$result->package_ID}-{$result->package_SERVER}'>Por no carrinho</button>
                    </div>";
    }

    public function getMostUsername()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_sales_username` ORDER BY `sale_AMOUNT` DESC LIMIT 1");
        $stmt->execute();

        if($stmt->rowCount() == 0)
        {
            return "";
        }

        $result = $stmt->fetchObject();

        return "<div class='card'><div class='card-body'><h5 class='text-white text-center'><small><b>Maior contribuinte</b></small></h5><br><p class='m-0 text-center'><img src='https://minotar.net/bust/{$result->sale_USERNAME}/150.png'></p> <br> <h5 class='text-white text-center'>{$result->sale_USERNAME}</h5></div></div>";
    }

    public function addUsernameAmount($username, $amount)
    {
        if(!$this->hasUsernameAmount($username))
        {
            $stmt = $this->getConnection()->prepare("INSERT INTO `website_sales_username`(`sale_USERNAME`, `sale_AMOUNT`) VALUES (?, ?)");
            return $stmt->execute([$username, $amount]);
        }
        $stmt = $this->getConnection()->prepare("UPDATE `website_sales_username` SET `sale_AMOUNT`=`sale_AMOUNT`+? WHERE `sale_USERNAME`=?");
        return $stmt->execute([$amount, $username]);
    }

    public function addSalePackage($package)
    {
        if(!$this->hasSalePackage($package))
        {
            $stmt = $this->getConnection()->prepare("INSERT INTO `website_sales_packages`(`sale_PACKAGE`, `sale_MONTH`) VALUES (?, ?)");
            return $stmt->execute([$package, date("m/Y")]);
        }
        $stmt = $this->getConnection()->prepare("UPDATE `website_sales_packages` SET `sale_COUNT`=`sale_COUNT`+1 WHERE `sale_PACKAGE`=? AND `sale_MONTH`=?");
        return $stmt->execute([$package, date("m/Y")]);
    }

    public function hasUsernameAmount($username)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_sales_username` WHERE `sale_USERNAME` = ?");
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }

    public function hasSalePackage($package)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_sales_packages` WHERE `sale_PACKAGE`=? AND `sale_MONTH`=?");
        $stmt->execute([$package, date("m/Y")]);
        return $stmt->rowCount() > 0;
    }

    public function info($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages` WHERE `package_ID`=?");
        $stmt->execute([$id]);

        $fetch = $stmt->fetchObject();

        return json_decode(json_encode([
            'name' => $fetch->package_NAME,
            'amount' => $fetch->package_AMOUNT
        ]));
    }

    private function tables()
    {
        $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_cart` ( `cart_ID` INT(11) NOT NULL AUTO_INCREMENT , `cart_USERNAME` VARCHAR(16) NOT NULL , `cart_PACKAGES` TEXT NOT NULL , `cart_CUPOM` VARCHAR(50) NOT NULL , `cart_CREATED_IN` DATETIME NOT NULL, `cart_IP` VARCHAR(100) NOT NULL , PRIMARY KEY (`cart_ID`)) ENGINE = InnoDB;")->execute();
        $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_sales_username` ( `sale_ID` INT(11) NOT NULL AUTO_INCREMENT , `sale_USERNAME` VARCHAR(16) NOT NULL , `sale_AMOUNT` DECIMAL(10,2) NOT NULL , PRIMARY KEY (`sale_ID`)) ENGINE = InnoDB;")->execute();
        $this->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_sales_packages` ( `sale_ID` INT(11) NOT NULL AUTO_INCREMENT, `sale_PACKAGE` INT(11) NOT NULL , `sale_MONTH` VARCHAR(7) NOT NULL , `sale_COUNT` INT(11) NOT NULL DEFAULT '1', PRIMARY KEY (`sale_ID`)) ENGINE = InnoDB;")->execute();
    }
}