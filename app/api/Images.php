<?php

namespace app\api;


use app\lib\Json;
use app\lib\Model;

class Images
{

    private $image_dir;


    public function save($file, $dir)
    {
        if ( isset( $file[ 'name' ] ) && $file[ 'error' ] == 0 ) {
            $arquivo_tmp = $file[ 'tmp_name' ];
            $nome = $file[ 'name' ];
            $extensao = pathinfo ( $nome, PATHINFO_EXTENSION );
            $extensao = strtolower ( $extensao );
            if ( strstr ( '.jpg;.jpeg;.png', $extensao ) ) {
                $novoNome = uniqid ( time () ) . '.' . $extensao;
                $destino = $dir . $novoNome;
                if (@move_uploaded_file ( $arquivo_tmp, $destino )) {
                    $this->image_dir = substr($destino, 1);
                } else
                {
                    return Json::encode(['response' => false, 'message' => 'Sistema sem permissão para mover a imagem']);
                }
            } else {
                return Json::encode(['response' => false, 'message' => 'Você poderá enviar apenas arquivos "*.jpg;*.jpeg;*.gif;*.png"']);
            }
        }else{
            return Json::encode(['response' => false, 'message' => 'Envie a imagem']);
        }
        return Json::encode(['response' => true, 'src' => $this->image_dir]);
    }

}