<?php

namespace app\controller\site;

use app\api\site\Profile;
use app\api\Tickets;
use app\api\Transactions;
use app\lib\Controller;
use app\lib\Forms;
use app\lib\Json;

class perfilController extends Controller
{

    public $profile, $forms, $tokenID, $tokenVALUE, $inputs, $transactions, $tickets;

    public function __construct()
    {
        parent::__construct();

        $this->profile = new Profile();
        $this->forms = new Forms();
        $this->transactions = new Transactions();
        $this->tickets = new Tickets();

        $this->setLayout("core");
    }

    public function index()
    {
        $this->view();
    }

    public function compras()
    {
        if(!$this->profile->isLogged())
        {
            header("Location: /perfil/login");
            return;
        }
        $this->view();
    }

    public function historico()
    {
        if(!$this->profile->isLogged())
        {
            header("Location: /perfil/login");
            return;
        }
        $this->view();
    }

    public function suporte()
    {

        if(!$this->profile->isLogged())
        {
            header("Location: /perfil/login");
            return;
        }
        $this->tokenID = $this->forms->getTokenID();
        $this->tokenVALUE = $this->forms->getToken();
        $this->inputs = $this->forms->formNames(['subject', 'order', 'body'], false);
        if($this->getParams(0) == "add")
        {
            $try =  $this->tickets->add($this->inputs);
            echo $try;
            $json = Json::decode($try);
            if($json->response == "ok")
            {
                $this->inputs = $this->forms->formNames(['subject', 'order', 'body'], true);
            }
            return;
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
        $this->view();
    }

    public function login()
    {
        $this->tokenID = $this->forms->getTokenID();
        $this->tokenVALUE = $this->forms->getToken();
        $this->inputs = $this->forms->formNames(['username', 'password', 'mode'], false);
        if($this->getParams(0) == "auth")
        {
            $try =  $this->profile->login($this->inputs);
            echo $try;
            $json = Json::decode($try);
            if($json->response == "ok")
            {
                $this->inputs = $this->forms->formNames(['username', 'password', 'mode'], true);
            }
            return;
        }
        if($this->getParams(0) == "logout")
        {
            unset($_SESSION['WebUserLogin']);
            header("Location: /perfil/login");
            return;
        }
        if($this->profile->isLogged())
        {
            header("Location: /perfil");
            return;
        }
        $this->view();
    }

    public function register()
    {
        $this->tokenID = $this->forms->getTokenID();
        $this->tokenVALUE = $this->forms->getToken();
        $this->inputs = $this->forms->formNames(['username', 'email', 'password', 'repeat'], false);
        if($this->getParams(0) == "run")
        {
            $try =  $this->profile->register($this->inputs);
            echo $try;
            $json = Json::decode($try);
            if($json->response == "ok")
            {
                $this->inputs = $this->forms->formNames(['username', 'email', 'password', 'repeat'], true);
            }
            return;
        }
        if($this->profile->isLogged())
        {
            header("Location: /");
            return;
        }
        $this->view();
    }

    public function recover()
    {
        $this->view();
    }

}