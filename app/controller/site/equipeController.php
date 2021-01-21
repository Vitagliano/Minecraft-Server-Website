<?php

namespace app\controller\site;

use app\api\site\Staff;
use app\lib\Controller;

class equipeController extends Controller
{

    public $staff;

    public function __construct()
    {
        parent::__construct();

        $this->staff = new Staff();

        $this->setLayout("core");
    }

    public function index()
    {
        $this->view();
    }

}