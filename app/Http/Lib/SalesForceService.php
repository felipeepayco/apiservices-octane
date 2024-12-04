<?php

namespace App\Http\Lib;

use App\Common\TiposPlanId;


class SalesForceService extends PreRegistroService
{

    public function getTokenSalesForce()
    {
        try {
            $url = getenv("SALESFORCE_URL") . "/services/oauth2/token";
            $variables = [
                "grant_type" => "password",
                "token" => getenv("TOKEN_SALESFORCE"),
                "client_id" => getenv("CLIENT_ID_SALESFORCE"),
                "client_secret" => getenv("CLIENT_SECRET_SALESFORCE"),
                "username" => getenv("USER_NAME_SALESFORCE"),
                "password" => getenv("PASSWORD_SALESFORCE")
            ];

            $tokenSalesforce = $this->sendCurlVariables($url, $variables, "POST");
            return $tokenSalesforce;
        } catch (\Exception $exception) {
            return $exception;

        }
    }

    public function setLeadSalesForce($token, $preRegistro)
    {

        try {

            $date = (new \DateTime('now'));
            $data = [
                'firstName' => $preRegistro->names != "" ? $preRegistro->names : $preRegistro->nombre_empresa,
                'lastName' => $preRegistro->surnames != "" ? $preRegistro->surnames : "empresa",
                'Email' => $preRegistro->email,
                'Fecha_Registro_ePayco__c' => $date->format('Y-m-d'),
                'Modelo_de_Afiliaci_n__c' => $preRegistro->plan_id == TiposPlanId::GATEWAY ? "Gateway" :"Agregador",
                'Tipo_de_Documento__c' => $preRegistro->doc_type,
                'Documento__c' => $preRegistro->doc_number,
                'Company' => $preRegistro->nombre_empresa != "" ? $preRegistro->nombre_empresa : $preRegistro->names,
                'de_Validaci_n__c' => '0',
                'Tipo_de_Usuario__c' => $preRegistro->user_type,
               // 'Registro ePayco' => "Dashboard",
                'MobilePhone'   => $preRegistro->cel_number,
                'En_que_pa_s_desea_procesar_con_ePayco__c' => 'Colombia'
            ];

            $authorization = array(
                "content-type: application/json",
                "Authorization: Bearer " . $token
            );
            $url = getenv("SALESFORCE_URL") . "/services/data/v55.0/sobjects/Lead";
            $this->sendCurlVariables($url, $data, "POST", true, $authorization);
        } catch (\Exception $exception) {
            return $exception;

        }
    }
}
