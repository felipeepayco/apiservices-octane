<?php
namespace App\Service\V2\Category\Validations;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ListCategoryValidation extends HelperPago
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
        $arr_respuesta = [];
        $filter = [];

        if (isset($data['clientId'])) {
            $clientId = (integer) $data['clientId'];
        } else {
            $clientId = false;
        }

        if (isset($data["filter"])) {

            if (isset($data["filter"]["countProducts"])) {
                if (!is_bool($data["filter"]["countProducts"])) {
                    $validate->setError(422, "countProducts field must be an boolean");

                }

            }

            if (is_array($data["filter"])) {
                $filter = (object) $data["filter"];
            } else if (is_object($data["filter"])) {
                $filter = $data["filter"];
            } else {
                $validate->setError(422, "field filter should be an object");
            }

        }

        $arr_respuesta["filter"] = $filter;

        $pagination = [];
        if (isset($data["pagination"])) {
            if (is_array($data["pagination"])) {
                $pagination = (object) $data["pagination"];

                if (isset($data["pagination"]["page"])) {
                    if (!$validate->validateIsNumeric($data["pagination"]["page"])) {
                        $validate->setError(422, "page field must be an integer");

                    } else {
                        if ($data["pagination"]["page"] < 1) {
                            $validate->setError(422, "page field must be greater than or equal to 1");

                        }
                    }

                }
                if (isset($data["pagination"]["limit"])) {
                    if (!$validate->validateIsNumeric($data["pagination"]["limit"])) {
                        $validate->setError(422, "limit field must be an integer");

                    } else {
                        if ($data["pagination"]["limit"] < 1) {
                            $validate->setError(422, "limit field must be greater than or equal to 1");

                        }
                    }
                }

            } else if (is_object($data["pagination"])) {
                $pagination = $data["pagination"];
            } else {
                $validate->setError(422, "field pagination should be an object");
            }
        }

        $arr_respuesta["pagination"] = $pagination;

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
            $this->saveLog(2, $clientId, '', $response, 'consult_catalogue_categories_list');

            return $response;
        }

        $arr_respuesta['success'] = true;
        return $arr_respuesta;

    }
}
