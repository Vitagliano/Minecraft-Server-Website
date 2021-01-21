<?php

namespace app\controller\site;

use app\api\admin\Block;
use app\api\admin\Discounts;
use app\api\admin\Settings;
use app\api\site\Cart;
use app\api\site\Checkout;
use app\api\site\Packages;
use app\api\site\PayPal;
use app\api\site\Profile;
use app\api\site\Servers;
use app\lib\Controller;

class lojaController extends Controller
{

    public $servers, $packages, $cart, $auth, $settings, $checkout, $block;

    public function __construct()
    {
        parent::__construct();

        $this->servers = new Servers();
        $this->packages = new Packages();
        $this->cart = new Cart();
        $this->auth = new Profile();
        $this->settings = new Settings();
        $this->checkout = new Checkout();
        $this->block = new Block();

        $this->setLayout("core");
    }

    public function index()
    {
        $this->view();
    }

    public function carrinho()
    {
        if($this->getParams(0) == "add")
        {
            echo $this->cart->add();
            return;
        }
        if($this->getParams(0) == "remove")
        {
            echo $this->cart->remove();
            return;
        }
        if($this->getParams(0) == 'att')
        {
            echo $this->cart->att();
            return;
        }
        if($this->getParams(0) == 'cupom')
        {
            $discounts = new Discounts();

            $amount    = $this->cart->totalAmount();

            if(!empty($_POST['hash']))
            {
                if($discounts->has($_POST['hash']))
                {
                    $type = $discounts->getType($_POST['hash']);
                    $amount = $discounts->apply($_POST['hash'], $type, $amount);
                }
            }

            echo 'R$'.number_format($amount, 2, ',', '.');
            return;
        }
        $this->view();
    }

    public function checkout()
    {
        echo $this->checkout->run();
        return;
    }

    public function notification()
    {
        echo $this->checkout->receive();
        return;
    }

    public function dopaypal()
    {
        $paypal = new PayPal();
        $paypal->AuthorizationCheckout(APP_ROOT.'/loja/notification', $_GET['token'], $_GET['PayerID']);
        header("Location: /");
        return;
    }

}