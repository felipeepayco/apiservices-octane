<?php

namespace App\Service\V2\Client\Validations;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use Illuminate\Http\Request;

class ListKeysClientValidation extends HelperPago
{
    private $catalogue_repository;
    private $client_repository;

    public function __construct(
        Request $request,
        CatalogueRepository $catalogue_repository,
        ClientRepository $client_repository,
    ) {
        parent::__construct($request);
        $this->catalogue_repository = $catalogue_repository;
        $this->client_repository = $client_repository;

    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(Request $request)
    {
        try {

            $fieldValidation = $request->all();

            if (!isset($fieldValidation["url"]) || $fieldValidation["url"] == "") {
                $arrResponse['success'] = false;
                $arrResponse['cod_error'] = 404;
                $arrResponse['titleResponse'] = "Invalid subdomain";
                $arrResponse['textResponse'] = "Invalid subdomain";
                $arrResponse['lastAction'] = "ValidationClientListKeys";
                $arrResponse['data'] = ["error" => "Invalid subdomain"];

                return $arrResponse;
            }
            $domainConfig = $this->buscarDominio($fieldValidation["url"]);
            if ($fieldValidation["clientId"] != getenv("CLIENT_ID_BABILONIA")) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = "Unauthorized";
                $arrResponse['textResponse'] = "Unauthorized";
                $arrResponse['lastAction'] = "ValidationClientListKeys";
                $arrResponse['data'] = ["error" => "Unauthorized"];

                return $arrResponse;
            }

            if (is_null($domainConfig)) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = "Subdomain or domain not exist";
                $arrResponse['textResponse'] = "Subdomain or domain  not exist";
                $arrResponse['lastAction'] = "ValidationClientListKeys";
                $arrResponse['data'] = ["error" => "Subdomain or domain not exist"];

                return $arrResponse;
            }

            $success = true;
            $title_response = 'Valid Subdomain';
            $text_response = 'Valid Subdomain';
            $last_action = 'Subdomain';
            $data = $domainConfig;

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error query to database";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;

    }
    private function buscarDominio($url)
    {
        $urlCompleta = $this->eliminarHttps($url);

        $urlCompleta = strtolower($urlCompleta);
        //buscar dominio propio
        $res = $this->buscarDominioPropio($urlCompleta);
        if ($res) {
            $domainConfig = $res;

        }
        //buscar sub dominio epayco
        if (!isset($domainConfig)) {

            $pattern = '/(.*?)\.(?=[^\/]*\..{2,3})/';

            if (preg_match($pattern, $urlCompleta, $match)) {
                $url = $match[0];

                $domain = config('app.BASE_URL_EPAYCO');
                $domainConfig = $this->consultarDominio($url, $domain);

                if (is_null($domainConfig)) {
                    $domain = config('app.BASE_URL_BBL');
                    $domainConfig = $this->consultarDominio($url, $domain);
                }

                if (is_null($domainConfig)) {
                    $domain = config('app.BASE_URL_SHOPS');
                    $domainConfig = $this->consultarDominio($url, $domain);
                }
            }
        }
        return $domainConfig;
    }
    private function buscarDominioPropio($url)
    {

        $dominio = $this->agregarWWW($url);

        $res = $this->consultaMongo($dominio);

        return $res;

    }
    private function eliminarHttps($url)
    {
        $url = str_replace("https://", "", $url);
        $url = str_replace("http://", "", $url);
        $url = str_replace("//", "", $url);
        return $url;
    }

    private function consultaMongo($dominio)
    {

        $arrDominio = explode(".", $dominio);

        $ownSubDomainValue = $arrDominio[0];

        $ownDomainValue = str_replace($ownSubDomainValue . ".", '', $dominio);

        $filter = [
            "valor_dominio_propio" => $ownDomainValue,
            "valor_subdominio_propio" => $ownSubDomainValue,

        ];

        $catalogo = $this->catalogue_repository->findByCriteria($filter);

        // funcionamiento para dominios propios
        if(is_null($catalogo)){
            $strucDomain=explode("/",$ownDomainValue);
            if(count($strucDomain)>1){
                $filter = [
                    "valor_dominio_propio" => $strucDomain[0],
                    "valor_subdominio_propio" => $ownSubDomainValue,
        
                ];   
                $catalogo = $this->catalogue_repository->findByCriteria($filter);
            }
        }
        return $catalogo;
    }
    private function extraerSubDominio($url)
    {
        $arrUrl = explode(".", $url);
        return $arrUrl[0];
    }
    private function extraerDominio($url)
    {
        $arrUrl = explode(".", $url);
        $arrUrlB = explode("//", $arrUrl[0]);
        return $arrUrlB[1];
    }
    private function agregarWWW($url)
    {
        $subDominio = $this->extraerSubDominio($url);

        if ($subDominio == "www") {
            return $url;
        } else {
            $cant = count(explode(".", $url));
            if ($cant < 3) {
                $domain = parse_url($url, PHP_URL_HOST);
                if(is_null($domain)){
                    return "www." . $url;
                }
                return "www." . $domain;
            } else {
                return $url;
            }
        }

    }

    private function consultarDominio($url, $domain)
    {
        $domain = parse_url($domain, PHP_URL_HOST);

        $url = $url . $domain;

        $domainConfig = $this->client_repository->consultDomain($url);
        return $domainConfig;

    }

}
