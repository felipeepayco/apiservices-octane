<?php

namespace App\Listeners\Country\Process;

use App\Events\Country\Process\ProcessGetConfCountriesEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Helpers\Respuesta\GeneralResponse;

use App\Models\ConfPais;
use App\Models\ConfMoneda;
use App\Models\PaisMoneda;
use App\Models\TipoDocumentos;

class ProcessGetConfCountriesListener extends HelperPago {

    const DOCUMENT_PERSON = 1;
    const DOCUMENT_COMMERCE = 1;
    /**
     * @param ProcessGetConfCountriesEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(ProcessGetConfCountriesEvent $event) {
        try {
            $confPaises = ConfPais::select("conf_pais.id")
                ->addSelect("paises.nombre_pais as countryName")
                ->addSelect("conf_pais.cod_pais as countryCode")
                ->addSelect("conf_pais.zona_horaria as timeZone")
                ->addSelect("conf_pais.url_flag as urlFlag")
                ->addSelect("paises.indicativo as callsign")
                ->join("paises", "paises.codigo_pais", "=", "conf_pais.cod_pais")
                ->get();

            $this->getCurrencies($confPaises);
            $this->getCountryDocuments($confPaises);
            $response = GeneralResponse::response(true, 'Success in obtaining the country configuration', 'Consult config countries', $confPaises);
        } catch (Exception $exception) {
            $data = array(
                'codError' => 500, 
                'errorMessage' => $exception->getMessage()
            );
            $response = GeneralResponse::response(false, 'Error in obtaining the countries configuration', 'Consult config countries', $data);
        }

        return $response;
    }

    private function getCurrencies(&$confPaises) {
        $confMonedas = ConfMoneda::select("id")
            ->addSelect("moneda as currency")
            ->addSelect("cod_moneda as currencyCode")
            ->addSelect("decimales as decimals")
            ->addSelect("separador as separator")
            ->get();

        $paisesMonedas = PaisMoneda::orderBy("principal", "desc")->get();

        foreach ($confPaises as &$confPais) {
            $auxConfMonedas = [];
            foreach ($paisesMonedas as $paisMoneda) {
                if ($paisMoneda->conf_pais_id === $confPais->id) {
                    foreach($confMonedas as $confMoneda) {
                        if ($confMoneda->id === $paisMoneda->conf_moneda_id) {
                            array_push($auxConfMonedas, $confMoneda);
                        }
                    }

                }
            }
            $confPais->currencies = $auxConfMonedas;
        }
    }

    private function getCountryDocuments(&$confPaises) {
        $typeDocuments = TipoDocumentos::select("id")
            ->addSelect("codigo as countryCode")
            ->addSelect("nombre as name")
            ->addSelect("descripcion as description")
            ->addSelect("persona as person")
            ->addSelect("empresa as commerce")
            ->addSelect("id_conf_pais")
            ->get();


        foreach ($confPaises as &$confPais) {
            $auxTypeDocuments = [];
            foreach ($typeDocuments as $documents) {
                if($confPais->id === $documents->id_conf_pais && ($documents->person === self::DOCUMENT_PERSON || $documents->commerce === self::DOCUMENT_COMMERCE)){
                    array_push($auxTypeDocuments, $documents);
                }
            }

            $confPais->typeDocuments = $auxTypeDocuments;
        }
    
    }
}