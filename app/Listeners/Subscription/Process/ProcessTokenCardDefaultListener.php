<?php

namespace App\Listeners\Subscription\Process;

use App\Events\Subscription\Process\ProcessTokenCardDefaultEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use App\Models\BblClientesCard;
use App\Models\BblPlan;
use App\Models\BblSuscripcion;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProcessTokenCardDefaultListener extends HelperPago
{
    private $arr_respuesta = [];

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function handle(ProcessTokenCardDefaultEvent $event)
    {

        $success = false;
        $titleResponse = 'Error';
        $textResponse = 'Error';
        $lastAction = 'Process token card default';
        $data = null;

        try {

            $params = $event->arr_parametros;
            $response = $this->addDefaultCardBbl([
                "customer_id" => $params["customerId"],
                "token" => $params["token"],
                "franchise" => $params["franchise"],
                "mask" => $params["mask"]
            ]);
            if ($response && !empty($response) && $response->status) {
                $newCardDefault = BblClientesCard::where('token',$params["token"])->first();
                $params2= [
                    "name" => $newCardDefault->firstname." ". $newCardDefault->lastname,
                    "doc_number" => $newCardDefault->doc_number, 
                    "doc_type" => $newCardDefault->doc_type, 
                    "phone" => $newCardDefault->phone,
                ];
                $response2 = $this->customerUpdate($params["customerId"], $params2);

                if ($response->status) {
                    $success = true;
                    $titleResponse = "Change Token card dafault success";
                    $textResponse = $response->message;
                    $data = $response->cards;
                } else {
                    $textResponse = 'Error actualizando los datos del customer';
                }

            }
        } catch (Exception $exception) {
            $success = false;
            $titleResponse = 'Error';
            $textResponse = "Error";
            $lastAction = 'fetch data from database';
            $error = (object)$this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }


        $arrResponse['success'] = $success;
        $arrResponse['titleResponse'] = $titleResponse;
        $arrResponse['textResponse'] = $textResponse;
        $arrResponse['lastAction'] = $lastAction;
        $arrResponse['data'] = $data;

        return $arrResponse;

    }

}
