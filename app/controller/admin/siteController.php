<?php

namespace app\controller\admin;

use app\api\admin\Accounts;
use app\api\admin\Permissions;
use app\api\admin\Posts;
use app\api\admin\Settings;
use app\api\admin\Staff;
use app\api\Changelogs;
use app\api\Email;
use app\api\Maintenance;
use app\lib\Controller;

class siteController extends Controller
{

    public $accounts, $maintenance, $settings, $emails, $changelogs, $staff, $posts, $permissions;

    public function __construct()
    {
        parent::__construct();

        $this->maintenance = new Maintenance();
        $this->accounts = new Accounts();
        $this->settings = new Settings();
        $this->emails = new Email();
        $this->changelogs = new Changelogs();
        $this->staff = new Staff();
        $this->posts = new Posts();
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
        header("Location: /admin");
    }

    public function postagens()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('site', 'postagens'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "add")
        {
            echo $this->posts->add();
            return;
        }
        if($this->getParams(0) == "edit")
        {
            echo $this->posts->edit();
            return;
        }
        if($this->getParams(0) == "delete")
        {
            echo $this->posts->delete();
            return;
        }
        $this->view();
    }

    public function equipe()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('site', 'equipe'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == 'edit')  {
            echo $this->staff->edit();
            return;
        }
        if($this->getParams(0) == 'delete')  {
            echo $this->staff->delete($this->getParams(1));
            return;
        }
        if($this->getParams(0) == 'adicionar')
        {
            if($this->getParams(1) == 'office')
            {
                echo $this->staff->addOffice();
                return;
            }
            if($this->getParams(1) == 'member')
            {
                echo $this->staff->addMember();
                return;
            }
        }
        $this->view();
    }

    public function atualizacoes()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('site', 'atualizacoes'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "add")
        {
            echo $this->changelogs->add();
            return;
        }
        if($this->getParams(0) == "delete")
        {
            echo $this->changelogs->delete();
            return;
        }
        if($this->getParams(0) == "edit")
        {
            echo $this->changelogs->edit();
            return;
        }
        $this->view();
    }

    public function emails()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('site', 'emails'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "edit")
        {
            echo $this->emails->save();
            return;
        }
        $this->view();
    }

    public function termos()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('site', 'termos'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "template")
        {
            echo $this->settings->setTerms();
            return;
        }
        $this->view();
    }

    public function manutencao()
    {
        if(!$this->accounts->logged())
        {
            header('Location: /admin/home/login');
            die();
        }
        if(!$this->permissions->parent('site', 'manutencao'))
        {
            header('Location: /admin');
            die();
        }
        if($this->getParams(0) == "alterate")
        {
            echo $this->maintenance->set();
            return;
        }
        if($this->getParams(0) == "template")
        {
            echo $this->maintenance->message();
            return;
        }
        $this->view();
    }

}