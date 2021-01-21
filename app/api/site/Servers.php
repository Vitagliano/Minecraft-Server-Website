<?php

namespace app\api\site;

use app\lib\Model;

class Servers extends Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function li()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_servers` WHERE `server_SHOW`=?");
        $stmt->execute([1]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $li = "";

        foreach ($fetch as $rs)
        {
            $li .= "<li><a href='/loja?s={$rs->server_ID}'>{$rs->server_NAME}</a></li>";
        }

        return $li;
    }

}