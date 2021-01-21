<?php

namespace app\lib;

class System extends Router {

    private $url,
            $exploder,
            $area,
            $controller,
            $runController,
            $action,
            $params;

    public function __construct()
    {
        $this->setUrl();
        $this->setExploder();
        $this->setArea();
        $this->setController();
        $this->setAction();
        $this->setParams();
    }

    private function setUrl()
    {
        $this->url = isset($_GET['url']) ? trim(strip_tags($_GET['url'])) : 'home/index';
    }

    private function setExploder()
    {
        $this->exploder = explode("/", $this->url);
    }

    private function setArea()
    {
        foreach ($this->routers as $index => $value)
        {
            if($this->onRaiz && $this->exploder[0] == $index)
            {
                $this->area   = $value;
                $this->onRaiz = false;
            }
        }

        $this->area = empty($this->area) ? $this->routerOnRaiz : $this->area;

        if(!defined('APP_AREA'))
        {
            define('APP_AREA', $this->area);
        }
    }

    public function getArea()
    {
        return $this->area;
    }

    private function setController()
    {
        $this->controller = $this->onRaiz ? $this->exploder[0] :
            (empty($this->exploder[1]) || is_null($this->exploder[1]) || !isset($this->exploder[1]) ? 'home' : $this->exploder[1]);
    }

    public function getController()
    {
        return $this->controller;
    }

    private function setAction()
    {
        $this->action = $this->onRaiz ?
            (!isset($this->exploder[1]) || is_null($this->exploder[1]) || empty($this->exploder[1]) ? 'index' : $this->exploder[1]) :
            (!isset($this->exploder[2]) || is_null($this->exploder[2]) || empty($this->exploder[2]) ? 'index' : $this->exploder[2]);
    }

    public function getAction()
    {
        return $this->action;
    }

    private function setParams()
    {
        if($this->onRaiz)
        {
            unset($this->exploder[0], $this->exploder[1]);
        }else{
            unset($this->exploder[0], $this->exploder[1], $this->exploder[2]);
        }

        if(end($this->exploder) == null)
        {
            array_pop($this->exploder);
        }

        if(empty($this->exploder))
        {
            $this->params = [];
        }else {
            $params = [];
            foreach ($this->exploder as $value)
            {
                $params[] = $value;
            }
            $this->params = $params;
        }
    }

    public function getParams($index)
    {
        return isset($this->params[$index]) ? $this->params[$index] : null;
    }

    private function validateController()
    {
        if(!(class_exists($this->runController)))
        {
            header('HTTP/1.0 404 Not Found');
            define('APP_ERROR', 'Não foi possível localizar o controller '.$this->controller);
            include("./app/content/".$this->area."/layouts/error.phtml");
        }
    }

    private function validateAction()
    {
        if(!(method_exists($this->runController, $this->action)))
        {
            header('HTTP/1.0 404 Not Found');
            define('APP_ERROR', 'Não foi possível localizar a action '.$this->action);
            include("./app/content/".$this->area."/layouts/error.phtml");
        }
    }

    public function Run()
    {
        $this->runController = 'app\\controller\\' . $this->getArea() . '\\'. $this->controller . 'Controller';

        $this->validateController();

        $this->runController = new $this->runController();

        $this->validateAction();

        $act = $this->action;

        $this->runController->$act();
    }
}