<?php

namespace app\controller\admin;

use app\api\admin\Accounts;
use app\api\admin\Block;
use app\api\admin\Capital;
use app\api\admin\Chargeback;
use app\api\admin\Discounts;
use app\api\admin\Packages;
use app\api\admin\Permissions;
use app\api\admin\Servers;
use app\api\Fraud;
use app\api\Transactions;
use app\lib\Controller;
use Dompdf\Dompdf;
use Dompdf\Options;

class lojaController extends Controller
{

    public $accounts, $servers, $packages, $discounts, $block, $capital, $permissions, $transactions, $fraud, $chargeback;

    public function __construct()
    {
        parent::__construct();

        $this->accounts = new Accounts();
        $this->servers = new Servers();
        $this->packages = new Packages();
        $this->discounts = new Discounts();
        $this->block = new Block();
        $this->capital = new Capital();
        $this->permissions = new Permissions();
        $this->transactions = new Transactions();
        $this->fraud = new Fraud();
        $this->chargeback = new Chargeback();

        $this->setLayout("core");
    }

    public function index()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        header("Location: /admin");
    }

    public function pacotes()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die;
        }
        if(!$this->permissions->parent('store', 'pacotes'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "liserver")
        {
            echo $this->servers->li();
            return;
        }

        if($this->getParams(0) == 'add') {
            echo $this->packages->add();
            return;
        }

        if($this->getParams(0) == 'edit') {
            echo $this->packages->edit();
            return;
        }

        if($this->getParams(0) == 'delete') {
            echo $this->packages->delete();
            return;
        }

        $this->view();
    }

    public function servidores()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            return;
        }
        if(!$this->permissions->parent('store', 'servidores'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "add")
        {
            echo $this->servers->add();
            return;
        }

        if($this->getParams(0) == "edit")
        {
            echo $this->servers->edit();
            return;
        }

        if($this->getParams(0) == "delete")
        {
            echo $this->servers->delete();
            return;
        }

        $this->view();
    }

    public function descontos()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            return;
        }
        if(!$this->permissions->parent('store', 'descontos'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "add")
        {
            echo $this->discounts->add();
            return;
        }

        if($this->getParams(0) == 'delete') {
            echo $this->discounts->delete();
            return;
        }

        $this->view();
    }

    public function capital()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('store', 'capital'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "addexpense")
        {
            echo $this->capital->addExpense();
            return;
        }
        if($this->getParams(0) == "paidexpense")
        {
            echo $this->capital->paidExpense();
            return;
        }
        if($this->getParams(0 ) == "addrate")
        {
            echo $this->capital->addApportionment();
            return;
        }
        $this->view();
    }

    public function estornos()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('store', 'estornos'))
        {
            header('Location: /admin');
            die();
        }
        $this->view();
    }

    public function ativar()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('store', 'ativar'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "list")
        {
            echo $this->packages->options();
            return;
        }
        if($this->getParams(0) == "set")
        {
            echo $this->transactions->active();
            return;
        }
        $this->view();
    }

    public function bloquear()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('store', 'bloquear'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "add")
        {
            echo $this->block->add();
            return;
        }
        if($this->getParams(0) == "delete")
        {
            echo $this->block->delete();
            return;
        }
        $this->view();
    }

    public function fraude()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('store', 'fraude'))
        {
            header('Location: /admin');
            die();
        }
        $this->view();
    }


    public function transacoes()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('store', 'transacoes'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == 'approve')
        {
            echo $this->transactions->approve();
            return;
        }
        $this->view();
    }

    public function report()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('store', 'transacoes'))
        {
            header('Location: /admin');
            die();
        }
        $html = file_get_contents('./app/content/site/layouts/report.phtml');

        $detail = $this->transactions->listInfo($this->getParams(0));

        $s = [ '%code', '%name', '%email', '%gross', '%net', '%fee', '%method', '%reference', '%date', '%products', '%table', '%now' ];
        $r = [
            $this->transactions->getTransactionCodeByID($this->getParams(0)),
            $detail['name'],
            $detail['email'],
            $detail['gross'],
            $detail['net'],
            $detail['fee'],
            $detail['method'],
            $detail['reference'],
            $detail['date'],
            $detail['products'],
            $this->transactions->tableNotificationsID($this->getParams(0)),
            date("d/m/Y à\s H:i")
        ];

        $html = str_replace($s, $r, $html);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->render();
        $pdf->stream('relatorio.pdf', [
            'Attachment' => false
        ]);
    }


}