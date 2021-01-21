<?php

namespace app\lib;

class Json
{

    public static function encode($data)
    {
        return json_encode($data);
    }

    public static function decode($data)
    {
        if(is_null($data))
        {
            return [ 'error' => 'Erro ao tentar decodificar' ];
        }
        return (object) json_decode($data);
    }

}