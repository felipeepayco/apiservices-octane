<?php
namespace App\Listeners\Vende\Process;

use App\Events\Vende\Process\ShowConfigurationDeliveryEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use App\Models\TipoDocumentos;
use Illuminate\Http\Request;

class ShowConfigurationDeliveryListener extends HelperPago
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
    public function handle(ShowConfigurationDeliveryEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $response = [];

            $client = BblClientes::find($clientId);

            $response["client"] = [
                "lastname" => $client["apellido"],
                "firstname" => $client["nombre"],
                "phone" => $client["telefono"],
                "document" => $client["documento"],
                "typeDoc" => $client["tipo_doc"],
                "business" => $client["razon_social"],
                "allyId" => $client["cliente_sdk_id"],
                "id" => $client["id"],
            ];

            $response["typesDoc"] = TipoDocumentos::findByCountryAndType();

            $success = true;
            $title_response = 'Datos cargados con exito';
            $text_response = "Datos cargados con exito";
            $last_action = "fetch data from database";
            $data = $response;

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error ' . $exception->getMessage();
            $text_response = "Error query to database ";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

}
