<?php
namespace App\Service\V2\Product\Validations;

use App\Helpers\Validation\ValidateError;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class DeleteProductValidation
{
    public $response;
    public function validate(Request $request)
    {

        $validate = new Validate();
        $data = $request->all();
        if (isset($data['clientId'])) {
            $clientId = (integer) $data['clientId'];
        } else {
            $clientId = false;
        }

        if (!isset($data['id']) || empty($data['id'])) {
            $validate->setError(422, "field id required");
        } else {
            $this->response['id'] = $data['id'];

            if (!$validate->validateIsNumeric($data['id'])) {

                $validate->setError(422, "id field is invalid, numeric value expected");
            } else {
                $id_length = floor(log10(abs($data['id']))) + 1;

                if ($id_length > 20) {

                    $validate->setError(422, "id field can not be greater than 20 digits");

                }

                if ($data['id'] < 1) {

                    $validate->setError(422, "id field must be greater than 0");

                }
            }

        }

        if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(422, "field clientId required");
            } else {
                $this->response['clientId'] = $clientId;
            }
        } else {
            $validate->setError(422, "field clientId required");
        }

        if ($validate->totalerrors > 0) {
            $this->response['success'] = false;
            $this->response = ValidateError::validateError($validate);
        } else {
            $this->response['success'] = true;

        }

        return $this->response;
    }
}
