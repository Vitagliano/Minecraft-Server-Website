<?php

namespace app\controller\site;

use app\api\site\Punish;
use app\lib\Controller;
use app\lib\Forms;

class punicoesController extends Controller
{

    public $punish, $forms, $tokenID, $tokenVALUE, $inputs;

    public function __construct()
    {
        parent::__construct();

        $this->punish = new Punish();
        $this->forms = new Forms();

        $this->setLayout("core");
    }

    public function index()
    {
        $this->tokenID    = $this->forms->getTokenID();
        $this->tokenVALUE = $this->forms->getToken();
        $this->inputs     = $this->forms->formNames(['search'], false);
        $this->view();
    }

}