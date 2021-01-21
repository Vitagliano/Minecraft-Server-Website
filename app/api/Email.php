<?php

namespace app\api;

use app\lib\Config;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{

    private $model;

    private $pending_payment = [
        '{name}',
        '{ip}',
        '{username}',
        '{packages}',
        '{cupom}',
        '{reference}',
        '{price}',
        '{date}',
        '{time}',
        '{gateway}',
        '{invoice}',
        '{transaction}'
    ];

    private $confirm_payment = [
        '{name}',
        '{ip}',
        '{username}',
        '{packages}',
        '{dateApproved}',
        '{reference}',
        '{price}',
        '{date}',
        '{time}',
        '{gateway}',
        '{invoice}',
        '{transaction}'
    ];

    private $atived_packages = [
        '{name}',
        '{username}',
        '{packages}',
        '{gateway}',
        '{date}',
        '{time}'
    ];

    private $ticket = [
        '{username}',
        '{ip}',
        '{matter}',
        '{date}',
        '{url}',
        '{id}',
        '{status}'
    ];


    public function __construct()
    {
        $this->model = new Model();
    }

    public function save()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }

        $column = "";

        switch ($_POST['type']) {
            case 'pending':
                $column = "settings_EMAIL_PURCHASE_PENDING";
                break;
            case 'confirmation':
                $column = "settings_EMAIL_PURCHASE_CONFIRMATION";
                break;
            case 'activated':
                $column = "settings_EMAIL_PURCHASE_ACTIVE";
                break;
            case 'opened':
                $column = "settings_EMAIL_TICKET_OPENED";
                break;
            case 'closed':
                $column = "settings_EMAIL_TICKET_CLOSED";
                break;
            case 'replied':
                $column = "settings_EMAIL_TICKET_REPLIED";
                break;
        }

        $stmt = $this->model->getConnection()->prepare("UPDATE `website_settings` SET `{$column}`=? WHERE `setting_ID`=?");
        $stmt->execute([ $_POST['html'], 1 ]);

        return Json::encode(['response' => 'ok', 'message' => 'E-mail atualizado']);
    }

    public function sendPaymentEmail($to, $data, $type)
    {

        if($type == 'pending') {
            $subject = "Pagamento pendente";
            $body = str_replace($this->pending_payment, $data, $this->body('pending'));
        }elseif($type == 'confirmation') {
            $subject = "Confirmação de Pagamento";
            $body = str_replace($this->confirm_payment, $data, $this->body('confirmation'));
        }else{
            $subject = "Confirmação de entrega";
            $body = str_replace($this->atived_packages, $data, $this->body('activated'));
        }

        return $this->send($to, $subject, $body);

    }

    public function body($type)
    {
        $column = "";
        switch ($type) {
            case 'pending':
                $column = "settings_EMAIL_PURCHASE_PENDING";
                break;
            case 'confirmation':
                $column = "settings_EMAIL_PURCHASE_CONFIRMATION";
                break;
            case 'activated':
                $column = "settings_EMAIL_PURCHASE_ACTIVE";
                break;
            case 'opened':
                $column = "settings_EMAIL_TICKET_OPENED";
                break;
            case 'closed':
                $column = "settings_EMAIL_TICKET_CLOSED";
                break;
            case 'replied':
                $column = "settings_EMAIL_TICKET_REPLIED";
                break;
        }
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings` WHERE `setting_ID`");
        $stmt->execute([1]);
        return $stmt->fetchObject()->$column;
    }

    private function send($to, $subject, $body)
    {
        $headers  = "From: MineRealm < noreply@minerealm.com.br >\n";
        $headers .= "X-Sender: MineRealm < noreply@minerealm.com.br >\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();
        $headers .= "X-Priority: 1\n";
        $headers .= "Return-Path: contato@minerealm.com.br\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=iso-8859-1\n";

        return mail($to, $subject, $body, $headers);
    }

}