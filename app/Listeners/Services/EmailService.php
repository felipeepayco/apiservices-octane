<?php

namespace App\Listeners\Services;

use App\Helpers\Pago\HelperPago;

class EmailService extends HelperPago
{

    public function __construct()
    {
        // comment
    }

    public function emailWithoutTemplatePanelRest($subject, $toEmail, $message)
    {
        $baseUrlRest = getenv("BASE_URL_REST");
        $basePanelAppRest = getenv("BASE_URL_APP_REST_ENTORNO");
        $body = [
            "subject" => $subject,
            "to" => $toEmail,
            "message" => $message
        ];
        $url = "{$baseUrlRest}/{$basePanelAppRest}/email/send/without/template";

        return $this->apiService($url, $body, "POST");
    }
    public function enviarEmailSuscripcionGratisDeprecated($subject, $toEmail,$clientName)
    {
        $baseUrlRest = getenv("BASE_URL_REST");
        $basePanelAppRest = getenv("BASE_URL_APP_REST_ENTORNO");
        $viewName="babilonia_activacion_prueba_gratis";
        $url = "{$baseUrlRest}/{$basePanelAppRest}/email/send?subject=$subject&toEmail=$toEmail&viewName=$viewName&viewParameters[clientName]=$clientName";
        $res= $this->sendCurlVariables($url, [], "GET", true);
        return $res;
    }

    public function enviarEmailSuscripcionGratis($subject, $toEmail,$clientName)
    {
        $notificationsUrl = config("app.MS_NOTIFICATIONS_BBL_URL");
        $url = "{$notificationsUrl}/email/send-free-trial-activation-email";
        $data = [
            "recipient" =>$toEmail,
            "clientName"=>$clientName
        ];
        $res= $this->sendCurlVariables($url, $data, "post", true);
        return $res;
    }
    
}
