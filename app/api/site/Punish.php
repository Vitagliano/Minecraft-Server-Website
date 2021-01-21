<?php

namespace app\api\site;

use app\api\admin\Settings;
use app\lib\Model;

class Punish
{

    private $model;

    public function __construct()
    {
        $settings = new Settings();
        $this->model = new Model();
        $this->model->set($settings->printDatabases('litebans')->host,
            $settings->printDatabases('litebans')->username,
            $settings->printDatabases('litebans')->password,
            $settings->printDatabases('litebans')->database);
    }

    public function recents()
    {
        $stmt = $this->model->getNewConnection()->prepare("SELECT * FROM `litebans_bans` ORDER BY `id` DESC LIMIT 20");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $rs)
        {
            $reason = $rs->reason;
            $date   = utf8_encode($this->toDate($rs->time));
            $cor    = ($rs->active == 1) ? '#f44336' : '#64dd17';

            $reason = explode(" - ", $reason);

            $reasonTEXT = $reason[0];
            $reasonPROVE = (isset($reason[1])) ? $reason[1] : '';

            if(!empty($reasonPROVE))
            {
                $reason = "<a href='$reasonPROVE' target='_blank' style='color: white'>".strtolower($reasonTEXT)."</a>";
            }else{
                $reason = strtolower($reasonTEXT);
            }

            $table.="<div class='card mb-2' style='background: {$cor}; font-size: 13px; color: #fff;'><div class='card-block p-3'><div class='row'><div class='col-md-8'><p class='m-0'> <img src='https://minotar.net/helm/{$this->username($rs->banned_by_uuid)}/40' class='img-fluid mr-2'>{$this->username($rs->banned_by_uuid)} puniu {$this->username($rs->uuid)} por {$reason}</p></div><div class='col-md-4'><p class='m-0 mt-2 text-center'>{$date} às {$this->toHours($rs->time)}</p></div></div></div></div>";
        }

        return $table;
    }

    public function search($username)
    {
        $uuid = $this->uuid($username);

        $stmt = $this->model->getNewConnection()->prepare("SELECT * FROM `litebans_bans` WHERE `uuid`=? ORDER BY `id` DESC");
        $stmt->execute([$uuid]);

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        if($stmt->rowCount() == 0)
        {
            return "<h5>Uau!!</h5><p>Pelo que procuramos, {$username} é um exemplo de jogador em nossa rede! <br><small class='text-muted'>nenhum resultado encontrado</small></p>";
        }



        foreach ($fetch as $rs)
        {
            $reason = $rs->reason;

            $reason = explode(" - ", $reason);

            $reasonTEXT = $reason[0];
            $reasonPROVE = (isset($reason[1])) ? $reason[1] : '';

            if(!empty($reasonPROVE))
            {
                $reason = "<a href='$reasonPROVE' target='_blank' style='color: white'>".strtolower($reasonTEXT)."</a>";
            }else{
                $reason = strtolower($reasonTEXT);
            }

            $date   = utf8_encode($this->toDate($rs->time));
            $cor    = ($rs->active == 1) ? '#f44336' : '#64dd17';

            $table.="<div class='card mb-2' style='background: {$cor}; font-size: 13px; color: #fff;'><div class='card-block p-3'><div class='row'><div class='col-md-8'><p class='m-0'> <img src='https://minotar.net/helm/{$this->username($rs->banned_by_uuid)}/40' class='img-fluid mr-2'>{$this->username($rs->banned_by_uuid)} puniu {$this->username($rs->uuid)} por {$reason}</p></div><div class='col-md-4'><p class='m-0 mt-2 text-center'>{$date} às {$this->toHours($rs->time)}</p></div></div></div></div>";
        }

        return $table;
    }

    public function row()
    {
        $stmt = $this->model->getNewConnection()->prepare("SELECT * FROM `litebans_bans`");
        $stmt->execute();
        return number_format($stmt->rowCount(), 0, ',', '.');
    }

    private function toDate($timestamp)
    {
        $date = strftime('%A, %d de %B de %Y', $this->convertToDate($timestamp));
        return $date;
    }

    private function toHours($timestamp)
    {
        $date = date("H:i", $this->convertToDate($timestamp));
        return $date;
    }

    function convertToDate($millis) {
        return $millis / 1000;
    }

    public function username($uuid)
    {
        $stmt = $this->model->getNewConnection()->prepare("SELECT * FROM `litebans_history` WHERE `uuid`=?;");
        $stmt->execute([$uuid]);

        if($stmt->rowCount() == 0)
        {
            return "undefined";
        }

        return $stmt->fetchObject()->name;
    }

    public function uuid($username)
    {
        $stmt = $this->model->getNewConnection()->prepare("SELECT * FROM `litebans_history` WHERE `name`=?;");
        $stmt->execute([$username]);
        if($stmt->rowCount() == 0)
        {
            return "undefined";
        }
        return $stmt->fetchObject()->uuid;
    }

}