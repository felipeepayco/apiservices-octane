<?php

namespace App\Http\Lib;

class ConsultControlListDaviviendaService
{

    /**
     * @param null $documentNumber
     * @param null $documentType
     * @return \Exception|mixed
     * Función que consume servicio para consultar las listas restrictivas
     */
    public function consultControlListDaviviendaService($documentNumber = null, $documentType = null)
    {
        try {
            $url = env("BASE_URL_API_DAVIVIENDA") . "/listas/control";
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\"numeroIdentificacion\":\"" . $documentNumber . "\",\"tipoIdentificacion\":\"" . $documentType . "\"}",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "accept: application/json",
                    "content-type: application/json",

                ),
            ));
            $resp = curl_exec($curl);
            curl_close($curl);
            $resp = json_decode($resp);
            return $resp[0];


        } catch (\Exception $exception) {
            return $exception;

        }
    }
}