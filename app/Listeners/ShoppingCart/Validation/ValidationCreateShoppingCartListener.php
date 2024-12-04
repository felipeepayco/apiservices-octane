<?php

namespace App\Listeners\ShoppingCart\Validation;


use App\Events\ShoppingCart\Validation\ValidationCreateShoppingCartEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Models\Catalogo;

class ValidationCreateShoppingCartListener extends HelperPago
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

    public function handle(ValidationCreateShoppingCartEvent $event)
    {

        $validate = new Validate();
        $data = $event->arr_parametros;

        if (isset($data['clientId'])) {
            $clientId = (int) $data['clientId'];
        } else {
            $clientId = false;
        }

        if (isset($data['catalogueId'])) {
            $catalogueId = (int) $data['catalogueId'];
        } else {
            $catalogueId = false;
        }

        if (isset($data['products'])) {
            $products = $data['products'];
        }

        if (isset($data['id'])) {
            $arr_respuesta['id'] = $data['id'];
        }

        if (isset($data['origin'])) {
            $arr_respuesta['origin'] = $data['origin'];
        }

        if (isset($data["filter"])) {
            if (is_array($data["filter"])) {
                $filter = (object) $data["filter"];
            } else if (is_object($data["filter"])) {
                $filter = $data["filter"];
            } else {
                $validate->setError(500, "field filter is type object");
            }
        } else {
            $filter = [];
        }

        $arr_respuesta["filter"] = $filter;

        if (isset($catalogueId)) {
            $vcatalogueId = $validate->ValidateVacio($catalogueId, 'catalogueId');
            if (!$vcatalogueId) {
                $validate->setError(500, "field catalogueId required");
            } else {
                $arr_respuesta['catalogueId'] = $catalogueId;
            }
        } else {
            $validate->setError(500, "field catalogueId required");
        }

        if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(500, "field clientId required");
            } else {
                $arr_respuesta['clientId'] = $clientId;
            }
        } else {
            $validate->setError(500, "field clientId required");
        }


        if (!isset($products)) {
            $validate->setError(500, "field products required");
        } else {
            $arr_respuesta['products'] = $products;
        }

        if ($validate->totalerrors > 0) {

            $success         = false;
            $last_action    = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data           =
                array(
                    'totalerrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage
                );
            $response = array(
                'success'         => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data
            );
            return $response;
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;
    }
}
