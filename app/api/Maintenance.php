<?php

namespace app\api;

use app\lib\Json;
use app\lib\Model;
use app\lib\RemoteAddress;
use app\lib\Security;

class Maintenance
{

    private $model;

    public function __construct()
    {
        $this->model = new Model();
    }

    public function set()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        $ips = "";
        $address = explode("\n", $_POST['ips']);
        foreach ($address as $ip)
        {
            $ips .= trim($ip)." ";
        }
        $stmt = $this->model->getConnection()->prepare("UPDATE `website_settings` SET `settings_MAINTENANCE_IPS`=? WHERE `setting_ID`=?");
        $stmt->execute([$ips, 1]);

        if($this->mode())
        {
            $message = "Modo desativado";
        }else{
            $message = "Modo ativado";
        }

        $this->alternate();

        return Json::encode(['response' => 'ok', 'message' => $message]);
    }

    public function show() {
        if($this->mode()) {
            $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings` WHERE `setting_ID`=1");
            $stmt->execute();
            $fetch = $stmt->fetchObject()->settings_MAINTENANCE_IPS;

            $explode = explode(" ", $fetch);

            $return = true;

            foreach ($explode as $ip)
            {
                if($ip != " ") {
                    if(trim($ip) == RemoteAddress::get())
                    {
                        $return = false;
                    }
                }
            }

            return $return;
        }
        return false;
    }

    public function message()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        $stmt = $this->model->getConnection()->prepare("UPDATE `website_settings` SET `settings_MAINTENANCE`=? WHERE `setting_ID`=1");
        $stmt->execute([ $_POST['html'] ]);

        return Json::encode(['response' => 'ok', 'message' => 'Template atualizado']);
    }

    public function mode()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings` WHERE `setting_ID`=?");
        $stmt->execute([1]);
        return $stmt->fetchObject()->settings_MAINTENANCE_MODE == 1;
    }

    public function htmlIPS()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings` WHERE `setting_ID`=1");
        $stmt->execute();

        $fetch = explode(" ", $stmt->fetchObject()->settings_MAINTENANCE_IPS);
        $html = "";
        foreach ($fetch as $result)
        {
            if($result != "")
            {
                $html .= trim($result)."\n";
            }
        }
        return $html;
    }

    public function htmlMESSAGE()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings` WHERE `setting_ID`=1");
        $stmt->execute();
        return $stmt->fetchObject()->settings_MAINTENANCE;
    }

    private function alternate()
    {
        $set = ($this->mode()) ? 0 : 1;
        $stmt = $this->model->getConnection()->prepare("UPDATE `website_settings` SET `settings_MAINTENANCE_MODE`=? WHERE `setting_ID`=?");
        $stmt->execute([ $set, 1 ]);
    }
}