<?php

namespace app\lib;

class Forms
{

    public static function isEmpty($method, $arry)
    {
        foreach ($arry as $item) {
            if(empty($method[$item]))
            {
                return true;
            }
        }
        return false;
    }

    public function getTokenID()
    {
        if(isset($_SESSION['token_id']))
        {
            return $_SESSION['token_id'];
        }
        $token_id = $this->random(10);
        $_SESSION['token_id'] = $token_id;
        return $token_id;
    }

    public function getToken()
    {
        if(isset($_SESSION['token_value']))
        {
            return $_SESSION['token_value'];
        }
        $token = hash('sha256', $this->random(500));
        $_SESSION['token_value'] = $token;
        return $token;
    }

    public function checkValid($method) {
        if(isset($method[$this->getTokenID()]) && ($method[$this->getTokenID()] == $this->getToken())) {
            return true;
        }
        return false;
    }

    public function formNames($names, $regenerate) {

        $values = array();
        foreach ($names as $n) {
            if($regenerate == true) {
                unset($_SESSION[$n]);
            }
            $s = isset($_SESSION[$n]) ? $_SESSION[$n] : $this->random(10);
            $_SESSION[$n] = $s;
            $values[$n] = $this->sqlinject($s);
        }
        return $values;
    }

    private function random($tamanho)
    {
        $lmin = 'abcdefghijklmnopqrstuvwxyz';
        $lmai = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $num = '1234567890';
        $simb = '!@#$%*-';
        $retorno = '';
        $caracteres = '';
        $caracteres .= $lmin;
        $caracteres .= $lmai;
        $caracteres .= $num;
        $caracteres .= $simb;
        $len = strlen($caracteres);
        for ($n = 1; $n <= $tamanho; $n++) {
            $rand = mt_rand(1, $len);
            $retorno .= $caracteres[$rand-1];
        }
        return $retorno;
    }

    private function sqlinject($str)
    {
        return addslashes(strip_tags($str));
    }

}