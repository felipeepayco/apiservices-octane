<?php

namespace App\Http\Controllers;

use App\Events\Vende\Process\ShowConfigurationDeliveryEvent;
use App\Helpers\Pago\HelperPago;
use Illuminate\Http\Request;

class ApiVendeShoppingcartController extends HelperPago
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function loadDataConfigDelivery(Request $request)
    {
        $arr_parametros = $request->request->all();
        $listener = event(
            new ShowConfigurationDeliveryEvent($arr_parametros),
            $request
        );

        $success = $listener[0]['success'];
        $title_response = $listener[0]['titleResponse'];
        $text_response = $listener[0]['textResponse'];
        $last_action = $listener[0]['lastAction'];
        $data = $listener[0]['data'];

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,

        );
        return $this->crearRespuesta($response);
    }

}
