<?php

namespace app\api\site;

use app\api\admin\Block;
use app\api\admin\Discounts;
use app\api\admin\Settings;
use app\api\Email;
use app\api\Fraud;
use app\api\Transactions;
use app\lib\Config;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Checkout extends Model
{

    const NOTIFICATION_URL = 'loja/notification';

    private $pagseguro, $paypal, $mp, $cart, $discounts, $packages, $settings, $fraud, $block, $profile, $email;

    public function __construct()
    {
        parent::__construct();

        $this->settings = new Settings();
        $this->pagseguro = new PagSeguro();
        $this->paypal = new PayPal();
        $this->discounts = new Discounts();
        $this->packages = new Packages();
        $this->mp = new \MP($this->settings->printGateway('mercadopago')->client_id, $this->settings->printGateway('mercadopago')->client_secret);
        $this->cart = new Cart();
        $this->fraud = new Fraud();
        $this->block = new Block();
        $this->profile = new Profile();
        $this->email = new Email();
    }

    public function blockPayPal()
    {
        return true;
        /*
        if($this->settings->paypalGlobal())
        {
            return true;
        }
        if($_SERVER['HTTP_CF_IPCOUNTRY'] != "BR")
        {
            return true;
        }
        return false;*/
    }


    public function run()
    {
        if(!Security::ajax()) { return Json::encode(['response' => 'error', 'message' => 'You\'re blocked by security system']); }

        $g = $_POST['gateway'];

        if($this->block->has($this->profile->username()))
        {
            return Json::encode(['response' => 'error', 'message' => 'Usuário bloqueado']);
        }

        $amount    = $this->cart->totalAmount();
        $reference = $this->addReference($this->cart->listPackages(), $this->profile->username(), $_POST['cupom'], $_POST['ip']);

        if(!empty($_POST['cupom']))
        {
            if($this->discounts->has($_POST['cupom']))
            {
                $type = $this->discounts->getType($_POST['cupom']);
                $amount = $this->discounts->apply($_POST['cupom'], $type, $amount);
            }
        }

        unset($_SESSION['WebCart']);

        if($g == "pagseguro")
        {
            return $this->pagseguro->checkout("Compra #".$reference, $amount, $reference,
                [
                    'REDIRECT' => APP_ROOT,
                    'NOTIFICATION' => APP_ROOT.Checkout::NOTIFICATION_URL
                ]);
        }
        if($g == "mercadopago")
        {
            $item = [
                "title" => "Compra #".$reference,
                "quantity" => 1,
                "currency_id" => "BRL",
                "unit_price" => (double) $amount
            ];

            $items = [ $item ];

            $backUrls = array("success" => APP_ROOT, "failure" => APP_ROOT, "pending" => APP_ROOT);

            $preference_data = [
                "items" => $items,
                "back_urls" => $backUrls,
                "auto_return" => "all",
                "notification_url" => APP_ROOT.Checkout::NOTIFICATION_URL,
                "external_reference" => $reference
            ];

            $preference = $this->mp->create_preference($preference_data);

            $url = $preference['response']['init_point'];
            return Json::encode(
                [
                    'response' => 'ok',
                    'url' => $url
                ]
            );
        }
        if($g == "paypal")
        {
            $this->paypal->addItem('Compra #'.$reference, (double) $amount, 1);
            $this->paypal->setCurrency('BRL');
            $this->paypal->setReference($reference);
            $this->paypal->setCancelURL(APP_ROOT);
            $this->paypal->setReturnURL(APP_ROOT);
            $this->paypal->setNotificationURL(APP_ROOT . Checkout::NOTIFICATION_URL);

            return Json::encode(
                [
                    'response' => 'ok',
                    'url' => $this->paypal->checkout()
                ]);
        }
        return "";
    }

    public function receive() {
        $transaction = new Transactions();

        if(!empty($_POST["notificationCode"])) {
            $code   = $_POST["notificationCode"];
            $r      = $this->pagseguro->notify($code);
            $code   = $r->code;
            $name   = $r->sender->name;
            $email  = $r->sender->email;
            $amount = $r->netAmount;
            $gross  = $r->grossAmount;
            $status = $r->status;
            $method_type = $r->paymentMethod->type;
            $method_code = $r->paymentMethod->code;
            $reference = $r->reference;
            $username = $this->getUsernameReference($reference);

            $transaction->save($code, $username, $reference, $status, $name, $email, $amount, $gross, $method_type, $method_code);

            if($status == 5)
            {
                $this->chargebacked($reference);
            }

            if($status == 1 )
            {
                $this->email->sendPaymentEmail($email, [
                    $name,
                    $this->getIpReference($reference),
                    $username,
                    '{packages}',
                    $this->getCupomReference($reference),
                    $reference,
                    'R$'.number_format((float) $gross, 2, ',', '.'),
                    date('d/m/Y'),
                    date('H:i'),
                    'PagSeguro',
                    'http://'.APP_ROOT.'/perfil/invoice/username/'.$reference,
                    $code
                ], 'pending');
            }


            if($status == 3)
            {
                $this->fraud->add($username);
                $this->packages->addUsernameAmount($username, $amount);
                $this->dispense($reference);

                if($this->getCupomReference($reference) != "")
                {
                    $this->discounts->addUsage($this->getCupomReference($reference));
                }

                $this->email->sendPaymentEmail($email, [
                    $name,
                    $this->getIpReference($reference),
                    $username,
                    '{packages}',
                    date('d/m/Y à\s H:i'),
                    $reference,
                    'R$'.number_format((float) $gross, 2, ',', '.'),
                    date('d/m/Y'),
                    date('H:i'),
                    'PagSeguro',
                    'http://'.APP_ROOT.'/perfil/invoice/username/'.$reference,
                    $code
                ], 'confirmation');

                $this->email->sendPaymentEmail($email, [
                    $username,
                    $username,
                    '{packages}',
                    'PagSeguro',
                    date("d/m/Y"),
                    date("H:i"),
                ], 'activated');
            }

            return "";
        }elseif ($_GET['topic'] == 'payment') {
            $payment_info = $this->mp->get_payment_info($_GET['id']);
            if ($payment_info["status"] == 200) {
                $data = $payment_info['response']['collection'];
                $code      = $data['id'];
                $status    = $data['status'];
                $gross     = $data['transaction_amount'];
                $amount    = $data['net_received_amount'];
                $method    = $data['payment_type'];
                $reference = $data['external_reference'];
                $username  = $this->getUsernameReference($reference);

                $name = (isset($data['payer']['first_name'])) ? $data['payer']['first_name']." ".$data['payer']['last_name'] : 'indefinido';
                $email     = $data['payer']['email'];
                if($amount == 0 || empty($amount))
                {
                    $amount = $this->somar($gross, ((4.99 * $gross) / 100));
                }

                $transaction->save($code, $username, $reference, $status,  $name, $email, $amount, $gross, $method, $method);

                if($status == 'pending')
                {
                    $this->email->sendPaymentEmail($email, [
                        $name,
                        $this->getIpReference($reference),
                        $username,
                        '{packages}',
                        $this->getCupomReference($reference),
                        $reference,
                        'R$'.number_format((float) $gross, 2, ',', '.'),
                        date('d/m/Y'),
                        date('H:i'),
                        'MercadoPago',
                        'http://'.APP_ROOT.'/perfil/invoice/username/'.$reference,
                        $code
                    ], 'pending');
                }

                if($status == 'in_mediation')
                {
                    $this->chargebacked($reference);
                }

                if($status == "approved") {
                    $this->fraud->add($username);
                    $this->packages->addUsernameAmount($username, $amount);
                    $this->dispense($reference);
                    if($this->getCupomReference($reference) != "")
                    {
                        $this->discounts->addUsage($this->getCupomReference($reference));
                    }
                    $this->email->sendPaymentEmail($email, [
                        $name,
                        $this->getIpReference($reference),
                        $username,
                        '{packages}',
                        date('d/m/Y à\s H:i'),
                        $reference,
                        'R$'.number_format((float) $gross, 2, ',', '.'),
                        date('d/m/Y'),
                        date('H:i'),
                        'MercadoPago',
                        'http://'.APP_ROOT.'/perfil/invoice/username/'.$reference,
                        $code
                    ], 'confirmation');

                    $this->email->sendPaymentEmail($email, [
                        $username,
                        $username,
                        '{packages}',
                        'MercadoPago',
                        date("d/m/Y"),
                        date("H:i"),
                    ], 'activated');
                }

            }
            return "";
        }else{
            $arry = [
                'id'        => $_POST['txn_id'],
                'status'    => $_POST['payment_status'],
                'reference' => $_POST['custom'],
                'amount'    => $_POST['mc_gross'] - $_POST['mc_fee'],
                'gross'     => $_POST['mc_gross'],
                'email'     => $_POST['payer_email'],
                'name'      => $_POST['first_name'] . " " . $_POST['last_name'],
                'method'    => $_POST['payment_type'],
                'paid'      => ($_POST['payment_status'] == 'Completed') ? 1 : 0
            ];

            $paypal = json_decode(json_encode($arry));

            $reference = $paypal->reference;
            $email     = $paypal->email;

            $code = $paypal->id;
            $name = $paypal->name;
            $method = $paypal->method;
            $gross = $paypal->gross;
            $amount = $paypal->amount;
            $status = $paypal->status;
            $username  = $this->getUsernameReference($reference);

            $transaction->save($code, $username, $reference, $status,  $name, $email, $amount, $gross, $method, $method);

            if($status == 'Pending')
            {
                $this->email->sendPaymentEmail($email, [
                    $name,
                    $this->getIpReference($reference),
                    $username,
                    '{packages}',
                    $this->getCupomReference($reference),
                    $reference,
                    'R$'.number_format((float) $gross, 2, ',', '.'),
                    date('d/m/Y'),
                    date('H:i'),
                    'PayPal',
                    'http://'.APP_ROOT.'/perfil/invoice/username/'.$reference,
                    $code
                ], 'pending');
            }

            if($status == 'Reversed')
            {
                $this->chargebacked($reference);
            }

            if($paypal->status == "Completed")
            {
                $this->fraud->add($username);
                $this->packages->addUsernameAmount($username, $amount);
                $this->dispense($reference);
                if($this->getCupomReference($reference) != "")
                {
                    $this->discounts->addUsage($this->getCupomReference($reference));
                }
                $this->email->sendPaymentEmail($email, [
                    $name,
                    $this->getIpReference($reference),
                    $username,
                    '{packages}',
                    date('d/m/Y à\s H:i'),
                    $reference,
                    'R$'.number_format((float) $gross, 2, ',', '.'),
                    date('d/m/Y'),
                    date('H:i'),
                    'PayPal',
                    'http://'.APP_ROOT.'/perfil/invoice/username/'.$reference,
                    $code
                ], 'confirmation');

                $this->email->sendPaymentEmail($email, [
                    $username,
                    $username,
                    '{packages}',
                    'PayPal',
                    date("d/m/Y"),
                    date("H:i"),
                ], 'activated');
            }
        }
        return "=)";
    }

    private function addReference($packages, $username, $cpm = "", $ip)
    {
        $save = "";
        foreach ($packages as $indice => $package) {
            $save .= "$indice:{$package['qnt']};";
        }
        $save = substr($save, 0, -1);
        $stmt = $this->getConnection()->prepare("INSERT INTO `website_cart`(`cart_USERNAME`, `cart_PACKAGES`, `cart_CUPOM`, `cart_CREATED_IN`, `cart_IP`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $save, $cpm, date("Y-m-d H:i:s"), $ip]);
        return $this->getConnection()->lastInsertId();
    }

    private function chargebacked($id)
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

            for ($i = 1; $i <= $quantity; $i++)
            {
                $commands = $this->getCommandsChargeback($package);
                foreach ($commands as $command)
                {
                    if($command->command_TYPE == 3)
                    {
                        $this->addDispenseChargebacked($username, $command->command_SERVER, str_replace('%p%', $username, $command->command_TOSEND));
                    }
                }
            }
        }
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

            $this->packages->addSalePackage($package);

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

    private function getCommandsChargeback($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages_commands` WHERE `command_PACKAGE`=? AND `command_TYPE`=?");
        $stmt->execute([$id, 3]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }


    private function getCommands($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_packages_commands` WHERE `command_PACKAGE`=? AND `command_TYPE`!=?");
        $stmt->execute([$id, 3]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    private function addDispenseChargebacked($username, $server, $command)
    {
        $date = date("Y-m-d");

        $sql = "INSERT INTO `website_packages_dispensation`(`dispense_USERNAME`, `dispense_SERVER`, `dispense_COMMAND`, `dispense_DATE`) VALUES (?, ?, ?, ?)";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([ $username, $server, $command, $date ]);
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

    private function getUsernameReference($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_cart` WHERE `cart_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->cart_USERNAME;
    }

    private function getIpReference($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_cart` WHERE `cart_ID`=?");
        $stmt->execute([$id]);
        return $stmt->fetchObject()->cart_IP;
    }

    private function getCupomReference($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_cart` WHERE `cart_ID`=?");
        $stmt->execute([$id]);
        return (empty($stmt->fetchObject()->cart_CUPOM)) ? '-/-' : $stmt->fetchObject()->cart_CUPOM;
    }

    private function somar($n1, $n2) { return $n1 - $n2;  }

    public function ip(){
        switch(true){
            case (!empty($_SERVER['HTTP_X_REAL_IP'])) : return $_SERVER['HTTP_X_REAL_IP'];
            case (!empty($_SERVER['HTTP_CLIENT_IP'])) : return $_SERVER['HTTP_CLIENT_IP'];
            case (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) : return $_SERVER['HTTP_X_FORWARDED_FOR'];
            default : return $_SERVER['REMOTE_ADDR'];
        }
    }

    public static function getMeta()
    {
        $transactions = new Transactions();

        $meta = Config::META;
        $total = $transactions->getEarnsInMonth(1);

        $result = ($total * 100) / $meta;
        $result = intval($result);

        if($result > 100)
        {
            return 100;
        }

        return $result;
    }
}