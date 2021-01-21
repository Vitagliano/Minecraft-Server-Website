<?php

namespace app\controller\site;

use app\api\Changelogs;
use app\lib\Controller;

class atualizacoesController extends Controller
{

    public $changelogs;

    public function __construct()
    {
        parent::__construct();

        $this->changelogs = new Changelogs();

        $this->setLayout("core");
    }

    public function index()
    {
        $this->view();
    }

}