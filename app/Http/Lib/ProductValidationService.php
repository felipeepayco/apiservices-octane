<?php

namespace App\Http\Lib;


class ProductValidationService
{
    public function productValidation($tipoIdentificacion, $numeroIdentificacion, $reference, $accountType)
    {
//        {"tipoIdentificacion":"CC","numeroIdentificacion":"1010205354","reference":"8274","accountType":"CA"}
        $arDatos = array('tipoIdentificacion' => $tipoIdentificacion,
            'numeroIdentificacion' => $numeroIdentificacion,
            'reference' => $reference,
            'accountType' => $accountType,
        );
        try {
            $jsonData = json_encode($arDatos);
            $baseUrlRest = env("BASE_URL_API_DAVIVIENDA");
            $urlService = "{$baseUrlRest}/product/validation";
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $urlService,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{$jsonData}",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "accept: application/json",
                    "content-type: application/json",

                ),
            ));
            $resp = curl_exec($curl);
            if ($resp === false) {
                return array('curl_error' => curl_error($curl), 'curerrno' => curl_errno($curl));
            }
            curl_close($curl);
            $resp = json_decode($resp);
            return $resp;


        } catch (\Exception $exception) {
            return $exception;

        }
    }
}