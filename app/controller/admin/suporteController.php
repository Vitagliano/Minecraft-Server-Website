<?php

namespace app\controller\admin;

use app\api\admin\Accounts;
use app\api\admin\Block;
use app\api\admin\Capital;
use app\api\admin\Discounts;
use app\api\admin\Packages;
use app\api\admin\Permissions;
use app\api\admin\Servers;
use app\api\Tickets;
use app\lib\Controller;

class suporteController extends Controller
{

    public $accounts, $tickets, $permissions;

    public function __construct()
    {
        parent::__construct();

        $this->accounts = new Accounts();
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
        if(!$this->permissions->parent('support', 'abertos'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "reply")
        {
            echo $this->tickets->reply();
            return;
        }
        if($this->getParams(0) == "close")
        {
            echo $this->tickets->close();
            return;
        }
        if($this->getParams(0) == "autoreply")
        {
            echo $this->tickets->autoreply();
            return;
        }
        $this->view();
    }

    public function logs()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            return;
        }
        if(!$this->permissions->parent('store', 'logs'))
        {
            header('Location: /admin');
            die();
        }
        $this->view();
    }

    public function mensagens()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            return;
        }
        if(!$this->permissions->parent('store', 'mensagens'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == 'add')
        {
            echo $this->tickets->addAutoMessage();
            return;
        }
        if($this->getParams(0) == 'delete')
        {
            echo $this->tickets->deleteMessage();
            return;
        }
        $this->view();
    }

}