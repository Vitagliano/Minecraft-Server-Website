<?php

namespace app\controller\admin;


use app\api\admin\Accounts;
use app\api\admin\Backup;
use app\api\admin\Permissions;
use app\api\admin\Settings;
use app\lib\Controller;
use app\lib\Forms;
use app\lib\Json;

class configuracoesController extends Controller
{

    public $accounts, $forms, $tokenID, $tokenVALUE, $inputs, $settings, $backup, $permissions;

    public function __construct()
    {
        parent::__construct();

        $this->accounts = new Accounts();
        $this->settings = new Settings();
        $this->forms = new Forms();
        $this->backup = new Backup();
        $this->permissions = new Permissions();

        $this->setLayout("core");
    }

    public function gateways()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('settings', 'gateways'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == 'set')
        {
            echo $this->settings->activeGateway();
            return;
        }
        if($this->getParams(0) == 'save')
        {
            echo $this->settings->saveGateways();
            return;
        }
        $this->view();
    }

    public function databases()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('settings', 'database'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == 'save')
        {
            echo $this->settings->saveDatabases();
            return;
        }
        $this->view();
    }

    public function backup()
    {
        if($this->getParams(0) == "save")
        {
            $this->backup->save();
            return;
        }
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('settings', 'backup'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "manual")
        {
            echo $this->backup->manual();
            return;
        }
        if($this->getParams(0) == 'download')
        {
            $this->backup->download($this->getParams(1));
            return;
        }
        $this->view();
    }

    public function usuarios()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            return;
        }
        if(!$this->permissions->parent('settings', 'usuarios'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == 'hash')
        {
            $lmin = 'abcdefghijklmnopqrstuvwxyz';
            $lmai = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $num = '1234567890';
            $retorno = '';
            $caracteres = '';
            $caracteres .= $lmin;
            if (true) $caracteres .= $lmai;
            if (true) $caracteres .= $num;
            $len = strlen($caracteres);
            for ($n = 1; $n <= 12; $n++) {
                $rand = mt_rand(1, $len);
                $retorno .= $caracteres[$rand-1];
            }
            echo $retorno;
            return;
        }
        $this->tokenID = $this->forms->getTokenID();
        $this->tokenVALUE = $this->forms->getToken();
        $this->inputs = $this->forms->formNames(['username', 'password'], false);
        if($this->getParams(0) == 'add')
        {
            $try = $this->accounts->add($this->inputs);
            echo $try;
            $json = Json::decode($try);
            if($json->response == "ok")
            {
                $this->inputs = $this->forms->formNames(['username', 'password'], true);
            }
            return;
        }
        if($this->getParams(0) == "delete")
        {
            echo $this->accounts->delete();
            return;
        }
        if($this->getParams(0) == "edit")
        {
            echo $this->accounts->edit();
            return;
        }
        $this->view();
    }
}