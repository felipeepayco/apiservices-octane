<?php

namespace App\Service\V2\Buyer\Validations;

use App\Http\Validation\Validate;
use App\Repositories\V2\BblBuyerRepository;
use Illuminate\Http\Request;

class BuyerListValidation
{

    protected BblBuyerRepository $buyer_repository;

    public function __construct(BblBuyerRepository $buyer_repository)
    {
        $this->buyer_repository = $buyer_repository;
    }

    public function validate(Request $request)
    {
        $validate = new Validate();
        $data = $request->request->all();
        $arr_respuesta = [];

        if (isset($data['clientId'])) {
            $clientId = (int) $data['clientId'];
        } else {
            $clientId = false;
        }

        if (!isset($data["filter"])) {
            $validate->setError(422, "field filter required");

        } else {
            $arr_respuesta["filter"] = $data["filter"];
            if (!isset($data['filter']['id'])) {
                $arr_respuesta['filter']['id'] = "";
            } else {

                if ($data['filter']['id'] != "") {
                    if (!$validate->validateIsNumeric($data['filter']['id'])) {
                        $validate->setError(422, "field id must be an integer");

                    } else {
                        if ($data['filter']['id'] <= 0) {
                            $validate->setError(422, "field id must be positive and greater than 0");

                        } elseif (strlen((string) $data['filter']['id']) > 10) {
                            $validate->setError(422, "the id field can not be greater than 10 digits ");

                        }
                    }
                }

            }
        }

        if (!isset($data["pagination"])) {
            $validate->setError(422, "field pagination required");

        } else {
            $arr_respuesta["pagination"] = $data["pagination"];

            if (isset($data["pagination"]["limit"])) {
                if (!$validate->validateIsNumeric($data['pagination']['limit'])) {
                    $validate->setError(422, "the limit field must be an integer ");

                }

                if ($data['pagination']['limit'] <= 0) {
                    $validate->setError(422, "the limit field must be greater than 0 ");

                }

            }

            if (isset($data["pagination"]["page"])) {
                if (!$validate->validateIsNumeric($data['pagination']['page'])) {
                    $validate->setError(422, "the page field must be an integer ");

                }

                if ($data['pagination']['page'] <= 0) {
                    $validate->setError(422, "the page field must be greater than 0 ");

                }
            }

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

        if ($validate->totalerrors > 0) {
            $success = false;
            $last_action = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
            array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            );
            $response = array(
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            );

            return $response;
        }

        $arr_respuesta["origin"] = "epayco";
        $arr_respuesta['success'] = true;

        return $arr_respuesta;
    }

}
