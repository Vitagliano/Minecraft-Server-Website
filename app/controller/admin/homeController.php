<?php

namespace app\controller\admin;

use app\api\admin\Accounts;
use app\api\admin\Permissions;
use app\api\admin\Posts;
use app\api\Tickets;
use app\api\Transactions;
use app\lib\Controller;
use app\lib\Forms;
use app\lib\Json;
use app\lib\Security;

class homeController extends Controller
{

    public $accounts, $forms, $tokenID, $tokenVALUE, $inputs, $posts, $transactions, $tickets, $permissions;

    public function __construct()
    {
        parent::__construct();

        $this->accounts = new Accounts();
        $this->forms = new Forms();
        $this->posts = new Posts();
        $this->transactions = new Transactions();
        $this->tickets = new Tickets();
        $this->permissions = new Permissions();

        $this->setLayout("core");
    }

    public function index()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        $this->view();
    }

    public function login()
    {
        if($this->getParams(0) == 'logout')
        {
            $this->accounts->logout();
            header('Location: /admin/home/login');
            die();
        }

        if($this->accounts->logged())
        {
           header('Location: /admin');
           return;
        }

        $this->tokenID = $this->forms->getTokenID();
        $this->tokenVALUE = $this->forms->getToken();
        $this->inputs = $this->forms->formNames(['username', 'password', 'ip', 'mode'], false);
        if($this->getParams(0) == "auth")
        {
            $try = $this->accounts->auth($this->inputs);
            echo $try;
            $json = Json::decode($try);
            if($json->response == "ok")
            {
                $this->inputs = $this->forms->formNames(['username', 'password', 'ip', 'mode'], true);
            }
            return;

        }
        $this->setLayout("login");
        $this->view();
    }

}