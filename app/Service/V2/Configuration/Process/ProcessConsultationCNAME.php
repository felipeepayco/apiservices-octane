<?php

namespace App\Service\V2\Configuration\Process;

use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\CloudflareRepository;


use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Helpers\Validation\CommonValidation;
use App\Repositories\V2\ClientRepository;

class ProcessConsultationCNAME extends HelperPago
{

	protected CatalogueRepository $catalogueRepository;
	protected CloudflareRepository $cloudflareRepository;
	protected ClientRepository $clientRepository;

	public function __construct(CatalogueRepository $catalogueRepository,CloudflareRepository $cloudflareRepository, ClientRepository $clientRepository)
	{
		$this->catalogueRepository = $catalogueRepository;
		$this->cloudflareRepository = $cloudflareRepository;
		$this->clientRepository = $clientRepository;
	}
	public function process($data)
	{
		try {
            $validate = false;
            $first = true;
            while (!$validate) {
                list($subdomain, $inUse) = $this->generateCname($data["clientId"], $first);
                // si no posee un cname en uso
                if (!$inUse) {
                    $success = $this->validateCname($subdomain);
                    // si no encuentro el cname salgo del ciclo y retorno el cname disponible
                    if (!$success) {
                        $validate = true;
                        $data = ["cname" => $subdomain];
                    } else {
                        $first = false;
                    }
                } else {
                    // si posee un cname en uso retorna el cname en uso
                    $validate = true;
                    $data = ["cname" => $subdomain];
                }
            }
            $success = true;
            $title_response = "babilonia_cname";
            $text_response = "sucess cname";
            $last_action = "babilonia_cname";
		} catch (\Exception $exception) {
			$success = false;
			$title_response = 'Error ';
			$text_response = "Error query to connection ";
			$last_action = 'fetch data from connection ';
			$error = $this->getErrorCheckout('E0100');
			$validate = new Validate();
			$validate->setError($error->error_code, $error->error_message);
			$data = array(
				'totalerrores' => $validate->totalerrors,
				'errores' =>
					$validate->errorMessage
			);
		}

		$arr_respuesta['success'] = $success;
		$arr_respuesta['titleResponse'] = $title_response;
		$arr_respuesta['textResponse'] = $text_response;
		$arr_respuesta['lastAction'] = $last_action;
		$arr_respuesta['data'] = $data;

		return $arr_respuesta;
	}

    function generateCname($clientId, $first) {
        $inUse = false;
        $data = $this->clientRepository->find($clientId);
        if (isset($data->url) && $data->url && $first && (!isset($data->cname) || $data->cname === null || $data->cname === '')) {
            $subdominio = $this->getSubdomain($data->url);
        } else if (isset($data->cname) && $data->cname !== null && $data->cname !== '') {
            $subdominio = $data->cname;
            $inUse = true;
        } else {
            $subdominio = 'babiloniacommerce' . rand(100, 999);
        }
        return [$subdominio, $inUse];
    }

    function validateCname($subdomain) {
        $result = (object)$this->cloudflareRepository->consultationSubdomain($subdomain);
        if ($result->success) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    function getSubdomain($url) {
        $urlParse = parse_url($url);
        if (isset($urlParse['host'])) {
            $partHost = explode('.', $urlParse['host']);
            if (count($partHost) >= 2) {
                $subdomain = $partHost[0];
                return $subdomain;
            }
        }
        return 'babiloniacommerce' . rand(100, 999);
    }
}
