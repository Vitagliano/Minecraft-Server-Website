<?php

namespace app\lib;

class Router {

    protected $routers = [
        'site' => 'site',
        'admin' => 'admin'
    ];
    protected $routerOnRaiz = 'site';
    protected $onRaiz = true;

}