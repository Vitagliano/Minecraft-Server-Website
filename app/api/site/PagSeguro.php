<?php

namespace app\api\site;

use app\api\admin\Settings;
use app\lib\Config;
use app\lib\Json;

class PagSeguro
{

    private $sandbox, $email, $token;

    public function __construct()
    {
        $settings = new Settings();
        $this->email = $settings->printGateway('pagseguro')->email;
        $this->token = $settings->printGateway('pagseguro')->token;
        $this->sandbox = Config::SANDBOX;
    }

    public function checkout($title, $price, $reference, $url)
    {
        $redirect = $url['REDIRECT'];
        $notification = $url['NOTIFICATION'];
        $price = number_format($price, '2', '.', '');
        $data['email'] = $this->email;
        $data['token'] = $this->token;
        $data['currency'] = 'BRL';
        $data['itemId1'] = uniqid();
        $data['itemDescription1'] = $title;
        $data['itemAmount1'] = $price;
        $data['itemQuantity1'] = '1';
        $data['reference'] = $reference;
        $data['redirectURL'] = $redirect;
        $data['notificationURL'] = $notification;

        $data = http_build_query($data);

        $curl = curl_init($this->getURLCheckout());

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $xml= curl_exec($curl);

        if($xml == 'Unauthorized'){
            return Json::encode(
                [
                    'response' => 'error',
                    'url' => 'PagSeguro: Dados de autenticação inválidos'
                ]);
            exit;
        }
        curl_close($curl);

        $xml= simplexml_load_string($xml);
        if(count($xml->error) > 0)
        {
            return Json::encode(
                [
                    'response' => 'error',
                    'url' => "PagSeguro: {$xml->error->message}"
                ]);
            exit;
        }

        if($this->sandbox)
        {
            $url = "https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=";
        }else{
            $url = "https://pagseguro.uol.com.br/v2/checkout/payment.html?code=";
        }

        return Json::encode(
            [
                'response' => 'ok',
                'url' => $url.$xml->code
            ]);
    }

    public function notify($code)
    {

        if($this->sandbox) {
            $url = "https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/notifications/$code?email={$this->email}&token={$this->token}";
        }else{
            $url = "https://ws.pagseguro.uol.com.br/v3/transactions/notifications/$code?email={$this->email}&token={$this->token}";
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $transaction = curl_exec($curl);
        curl_close($curl);
        return simplexml_load_string($transaction);
    }

    private function getURLCheckout()
    {
        return ($this->sandbox) ? 'https://ws.sandbox.pagseguro.uol.com.br/v2/checkout' : 'https://ws.pagseguro.uol.com.br/v2/checkout';
    }
}