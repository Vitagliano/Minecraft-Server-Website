<?php

namespace app\lib;

class Controller extends System {

    public $datas,
           $layout;

    private $path,
            $pathRender;

    protected $captionController, $captionAction, $captionParams;

    public function __construct()
    {
        parent::__construct();
    }

    public function active($page)
    {
        return ($this->getController() == $page) ? ' active' : '';
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }
    
    private function setPath($render)
    {
        if(is_array($render))
        {
            foreach ($render as $li) {
                $path = 'app/view'. $this->getArea() . '/' . $this->getController() . '/'  . $li . '.phtml';
                $this->fileExists($path);
                $this->path[] = $path;
            }
        }else{
            $this->pathRender = is_null($render) ? $this->getAction() : $render;
            $this->path = 'app/view/'.$this->getArea().'/'.$this->getController().'/'.$this->pathRender.'.phtml';
            $this->fileExists($this->path);
        }
    }

    public function destroyPath()
    {
        unset($this->path);
        unset($this->pathRender);
    }

    private function fileExists($file)
    {
        if (!file_exists($file))
        {
            header('HTTP/1.0 404 Not Found');
            define('APP_ERROR', 'Não foi possível localizar o arquivo '.$file);
            include("./app/content/".$this->getArea()."/layouts/error.phtml");
            die();
        }
    }

    public function view($render = null)
    {
        $this->setPath($render);

        if(is_null($this->layout))
        {
            $this->render();
        }else{
            $this->layout = 'app/content/'.$this->getArea().'/layouts/'.$this->layout.'.phtml';
            if(file_exists($this->layout))
            {
                $this->render($this->layout);
            }else{
                header('HTTP/1.0 404 Not Found');
                define('APP_ERROR', 'Não foi possível localizar o layout');
                include("./app/content/".$this->getArea().'/layouts/error.phtml');
                die();
            }
        }
    }


    public function render($file = null) {
        if(is_array($this->datas) && count($this->datas) > 0) {
            extract($this->datas, EXTR_PREFIX_ALL, 'view');
            extract(array(
                'controller' => (is_null($this->captionController) ? '' : $this->captionController),
                'action'     => (is_null($this->captionAction) ? '' : $this->captionAction),
                'params'     => (is_null($this->captionParams) ? '' : $this->captionParams)), EXTR_PREFIX_ALL, 'caption');
        }
        if(!is_null($file) && is_array($file)) {
            foreach ($file as $li) {
                include($li);
            }
        }elseif(is_null($file) && is_array($this->path)) {
            foreach ($this->path as $li) {
                include($li);
            }
        }else{
            $file = is_null($file) ? $this->path : $file;
            file_exists($file) ? include($file) : die($file);
        }
    }
}