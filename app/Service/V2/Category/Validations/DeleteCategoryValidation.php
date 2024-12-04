<?php
namespace App\Service\V2\Category\Validations;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class DeleteCategoryValidation extends HelperPago
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(Request $request)
    {
        $validate = new Validate();
        $data = $request->all();
        if (isset($data['clientId'])) {
            $clientId = (integer) $data['clientId'];
        } else {
            $clientId = false;
        }

        if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(422, "field clientId required");
            } else {
                $arr_respuesta['clientId'] = $clientId;
            }
        } else {
            $validate->setError(422, "field clientId required");
        }

        if (isset($data['id'])) {
            $vid = $validate->ValidateVacio($data['id'], 'id');
            if (!$vid) {
                $validate->setError(422, "field id required");
            } else {
                $arr_respuesta['id'] = $data['id'];
            }

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
        } else {
            $validate->setError(422, "field id required");
        }

    

        if ($validate->totalerrors > 0) {
            $success = false;
            $last_action = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
            array('totalerrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage);
            $response = array(
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            );
            //dd($response);
            $this->saveLog(2, $clientId, '', $response, 'consult_delete_sell');

            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;

    }
}
