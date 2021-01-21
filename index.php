<?php

session_start();

header_remove( 'X-Powered-By' );
header("X-XSS-Protection: 1; mode=block");
header("X-WebKit-CSP: policy");
header('Content-Type: text/html; charset=utf-8');


ini_set('display_errors',1);
ini_set('display_startup_erros',1);
error_reporting(E_ALL);

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

define('DOMAIN', "redeuniverse.net");

if (! empty($_SERVER['HTTPS'])) {
    $config['base_url'] = 'https://'.DOMAIN.'/';
} else {
    $config['base_url'] = 'https://'.DOMAIN.'/';
}

define('APP_ROOT', $config['base_url']);


require_once 'app/helper/Autoload.php';
require_once 'vendor/autoload.php';

use app\lib\System;
use app\lib\Model;
use app\api\Maintenance;

$maintenance = new Maintenance();
$model = new Model();
$System = new System();

if($maintenance->show()) {
    if($System->getArea() != "admin") {
        define('MAINTENACE_MESSAGE', $maintenance->htmlMESSAGE());
        include("app/content/site/layouts/maintenance.phtml");
        return;
    }
}
$System->Run();

$model->closeConnection();