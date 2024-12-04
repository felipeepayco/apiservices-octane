<?php

namespace App\Service\V2\Catalogue\Validations;

use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use App\Repositories\V2\CatalogueRepository;
use Illuminate\Http\Request;

class CatalogueChangeStatusValidation extends HelperPago
{

    protected CatalogueRepository $catalogueRepository;

    public function __construct(CatalogueRepository $catalogueRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
    }
    public function validate2(Request $request)
    {
        $validate = new Validate();
        $data = $request->request->all();

        $clientId = CommonValidation::validateIsSet($data, 'clientId', false, 'int');
        $id = CommonValidation::validateIsSet($data, 'id', '', 'int');
        $this->validateNumericParameters($data, 'id', 20, $validate);
        CommonValidation::validateParamFormat($data, $validate, $id, 'id', 'int', true);

        $active = CommonValidation::validateIsSet($data, 'active', null, 'bool');

        if (isset($data["active"])) {
            if (!is_bool($data["active"])) {
                $validate->setError(422, "active field must be a boolean");

            }
        } else {
            $validate->setError(422, "active field is required");

        }
        CommonValidation::validateParamFormat($data, $validate, $active, 'active', 'bool', true);


        $catalog = $this->catalogueRepository->findByIdAndClientIdNoEstatus($id, $clientId);

        if (!count($catalog)) {
            $validate->setError(422, "Catalog not found");

        }

        if ($validate->totalerrors > 0) {
            $success = false;
            $last_action = 'validation data save';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data = [
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            ];
            $response = [
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            ];

            return $response;
        }

        $response = [];
        $response['success'] = true;
        $response['data'] = $data;
        $response['titleResponse'] = "catalog is valid";
        $response['textResponse'] = "catalog is valid";

        return $response;
    }

    private function validateNumericParameters($data, $parameter_name, $length, $validate, $allow_zero = false)
    {
        if (isset($data[$parameter_name])) {
            $zero = ($allow_zero) ? $data[$parameter_name] != "0" : $data[$parameter_name] != "";

            if ($zero) {
                if (!$validate->validateIsNumeric($data[$parameter_name])) {

                    $validate->setError(422, "{$parameter_name} field is invalid, numeric value expected");
                } else {
                    $parameter_length = floor(log10(abs($data[$parameter_name]))) + 1;

                    if ($parameter_length > $length) {

                        $validate->setError(422, "$parameter_name field can not be greater than $length digits");

                    }

                    if ($data[$parameter_name] < 1) {

                        $validate->setError(422, "$parameter_name field must be greater than 0");

                    }
                }
            }

        }
    }

}
