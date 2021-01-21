<?php

namespace app\controller\site;

use app\api\Email;
use app\api\site\Posts;
use app\lib\Controller;

class homeController extends Controller
{

    public $posts;

    public function __construct()
    {
        parent::__construct();

        $this->posts = new Posts();

        $this->setLayout("core");
    }

    public function index()
    {
        $this->view();
    }

    public function postagem()
    {
        $this->view();
    }

}