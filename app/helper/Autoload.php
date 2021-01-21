<?php

spl_autoload_register(function($class){
    $file = str_replace(substr_replace("\ ", '', -1), "/", $class) . ".php";
    if(file_exists($file)) {
        require_once $file;
    }
});