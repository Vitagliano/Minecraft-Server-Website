<?php

namespace app\lib;

class Security
{

    public static function ajax()
    {
        if(strpos($_SERVER['HTTP_REFERER'], 'localhost'))
        {
            return true;
        }
        return strpos($_SERVER['HTTP_REFERER'], DOMAIN);
    }
}