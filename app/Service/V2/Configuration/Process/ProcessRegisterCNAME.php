<?php

namespace App\Service\V2\Configuration\Process;

use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\CloudflareRepository;


use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Helpers\Validation\CommonValidation;

class ProcessRegisterCNAME extends HelperPago
{

	protected CatalogueRepository $catalogueRepository;
	protected CloudflareRepository $cloudflareRepository;

	public function __construct(CatalogueRepository $catalogueRepository,CloudflareRepository $cloudflareRepository)
	{
		$this->catalogueRepository = $catalogueRepository;
		$this->cloudflareRepository = $cloudflareRepository;
	}
	public function process($data)
	{
		try {
			if (isset($data["subdomain"])) {
				$result = $this->cloudflareRepository->registerSubdomain($data["subdomain"]);
				$fieldValidation = $result;
				$data = $fieldValidation;
				if ($result->success) {
					$success = true;
					$title_response = "babilonia_cname";
					$text_response = "sucess cname";
					$last_action = "babilonia_cname";
				} else {
					$success = false;
					$title_response = "babilonia_cname";
					$text_response = "error cname";
					$last_action = "babilonia_cname";
				}
			} else {
				$data = [];
				$success = false;
				$title_response = "subdomain is requerid";
				$text_response = "error cname";
				$last_action = "babilonia_cname";

			}
		} catch (\Exception $exception) {
			$success = false;
			$title_response = 'Error ' . $exception->getMessage();
			$text_response = "Error query to connection " . $exception->getFile();
			$last_action = 'fetch data from connection ' . $exception->getLine();
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
}
