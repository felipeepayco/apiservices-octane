<?php

namespace App\Listeners\Buttons\Process;

use App\Events\Buttons\Process\ConsultSellNewButtonEvent;
use App\Helpers\Cobra\HelperCobra;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\BotonesPago;
use App\Models\LlavesClientes;
use App\Models\Trm;
use App\Models\WsFiltrosClientes;
use App\Models\WsFiltrosDefault;
use Illuminate\Http\Request;
use stdClass;

class ConsultSellNewButtonListener extends HelperPago
{

    protected $baseUrlRest = "";
    protected $baseUrlEntornoAppRest = "";
    protected $baseUrlPaycoLink = "https://payco.link";

    /**
     * Create the event listener.
     *
     * @param Request $request request
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->baseUrlRest = getenv("BASE_URL_REST");
        $this->baseUrlEntornoAppRest = getenv("BASE_URL_APP_REST_ENTORNO");
    }

    /**
     * Handle the event.
     *
     * @param ConsultSellNewButtonEvent $event Evento de crear link de cobro
     *
     * @return void
     */
    public function handle(ConsultSellNewButtonEvent $event)
    {
        try {
            return $this->createCobro($event);
        } catch (Exception $exception) {
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);

            $arr_respuesta = new stdClass();
            $arr_respuesta->success = false;
            $arr_respuesta->titleResponse = 'Error';
            $arr_respuesta->textResponse = "Error inesperado al crear el cobro con los parámetros datos";
            $arr_respuesta->lastAction = 'fetch data from database';
            $arr_respuesta->data = [
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage
            ];

            return (array)$arr_respuesta;
        }
    }

    /**
     * Retornar la respuesta de error
     *
     * @param string $text_response Mensaje de error
     *
     * @return array
     */
    protected function getErrorResponse($text_response)
    {
        $arr_respuesta = new stdClass();
        $arr_respuesta->success = false;
        $arr_respuesta->titleResponse = 'Error';
        $arr_respuesta->textResponse = $text_response;
        $arr_respuesta->lastAction = 'create new sell';
        $arr_respuesta->data = [];

        return (array)$arr_respuesta;
    }

    /**
     * Formateo la data a devolver
     *
     * @param BotonesPago $botonPago El nuevo cobro
     *
     * @return void
     */
    protected function getNewData($botonPago)
    {
        return [
            "clientId" => $botonPago->id_cliente,
            "amountBase" => $botonPago->amount_base,
            "description" => $botonPago->descripcion,
            "detalle" => $botonPago->detalle,
            "referencia" => $botonPago->referencia,
            "currency" => $botonPago->moneda,
            "typeSell" => $botonPago->tipo,
            "urlConfirmation" => $botonPago->url_confirmacion,
            "urlResponse" => $botonPago->url_respuesta,
            "urlImagen" => $botonPago->url_imagen,
            "urlImagenexterna" => $botonPago->url_imagenexterna,
            "tipo" => $botonPago->tipo,
            "tax" => $botonPago->tax,
            "icoTax" => $botonPago->ico,
            "amount" => $botonPago->valor,
            "id" => $botonPago->Id
        ];
    }

    /**
     * Retorna el monto maximo del cobro
     *
     * @param mixed $clientId El id del cliente
     *
     * @return mixed
     */
    protected function getMontoMax($clientId)
    {
        $filtroMontoMax = WsFiltrosClientes::Where("id_cliente", "=", $clientId)
            ->where("filtro", "1")->first();

        if ($filtroMontoMax) {
            return $filtroMontoMax->valor;
        } else {
            $filtroMontoMaxDefault = WsFiltrosDefault::where("filtro", 1)->first();
            return $filtroMontoMaxDefault->valor;
        }
    }

    /**
     * Hacer la conversion por el tipo de moneda
     *
     * @param mixed $currency La moneda
     * @param mixed $amount La cantidad
     *
     * @return mixed
     */
    protected function getMontoOk($currency, $amount)
    {
        if ($currency == 'COP') {
            return $amount;
        } else {
            $objtrm = Trm::where("Id", 1)->first();
            $trm = $objtrm->trm_actual;
            return $amount * $trm;
        }
    }

    /**
     * Retorna el valor de Base
     *
     * @param array $fieldValidation Los parámetros
     *
     * @return mixed
     */
    protected function getBase($fieldValidation)
    {
        return isset($fieldValidation["base"]) ? $fieldValidation["base"] : 0;
    }

    /**
     * Retorna el valor de Tax
     *
     * @param array $fieldValidation Los parámetros
     *
     * @return mixed
     */
    protected function getTax($fieldValidation)
    {
        return isset($fieldValidation["tax"])
        && $fieldValidation["tax"] !== ""
            ? $fieldValidation["tax"]
            : 0;
    }

    /**
     * Retorna el valor de Ico
     *
     * @param array $fieldValidation Los parámetros
     *
     * @return mixed
     */
    protected function getIcoTax($fieldValidation)
    {
        $field = "icoTax";
        return isset($fieldValidation[$field])
        && !empty($fieldValidation[$field])
            ? $fieldValidation[$field]
            : null;
    }

    /**
     * Lógica para crear el boton de pago
     *
     * @param ConsultSellNewButtonEvent $event Evento
     *
     * @return mixed
     */
    protected function createCobro(ConsultSellNewButtonEvent $event)
    {
        $fieldValidation = $event->arr_parametros;
        $clientId = $fieldValidation["clientId"];
        $id = $fieldValidation["id"];
        $amount = $fieldValidation["amount"];
        $currency = $fieldValidation["currency"];
        $base = $this->getBase($fieldValidation);
        $description = $fieldValidation["description"];
        $type = $fieldValidation["type"];
        $detail = $fieldValidation["detail"];
        $reference = $fieldValidation["reference"];
        $urlConfirmation = $fieldValidation["urlConfirmation"];
        $urlResponse = $fieldValidation["urlResponse"];
        $urlImagen = $fieldValidation["urlImage"];
        $urlImagenexternal = $fieldValidation["urlImageexternal"];
        $tax = $this->getTax($fieldValidation);
        $ico = $this->getIcoTax($fieldValidation);


        $total_ok = $this->getMontoOk($currency, $amount);

        $helperCobra = new HelperCobra($event->request, $clientId);

        $valid_porcentaje = $helperCobra->validateAmounts($amount);

        $text_response = null;

        // Validación del monto máximo
        $cantmaxcobro = $this->getMontoMax($clientId);
        if (($total_ok > $cantmaxcobro) || (!$valid_porcentaje)) {
            $totalMax = number_format($cantmaxcobro);
            $text_response = trans(
                "message.Maximum amount exceeded, the maximum amount allowed for your account is :totalMax COP",
                ['totalMax' => $totalMax]
            );
        }




        if ($id > 0) {
            $botonPago = BotonesPago::where("id", $id)->where("id_cliente", $clientId)->first();
        } else {
            $botonPago = new BotonesPago();
        }

        $llavesCLiente = LlavesClientes::where("cliente_id", $clientId)->first();
        // Validación si existe un cobro con la referencia
        $llavesCLiente = $llavesCLiente->getPublicKey();

        if ($text_response) {
            return $this->getErrorResponse($text_response);
        }

        $botonPago->id_cliente = $clientId;
        $botonPago->amount_base = $base;
        $botonPago->descripcion = $description;
        $botonPago->detalle = $detail;
        $botonPago->referencia = $reference;
        $botonPago->moneda = $currency;
        $botonPago->url_confirmacion = $urlConfirmation;
        $botonPago->url_respuesta = $urlResponse;
        $botonPago->tax = $tax;
        $botonPago->ico = $ico;
        $botonPago->tipo = $type;
        $botonPago->valor = $amount;
        $botonPago->url_imagen = $urlImagen;
        $botonPago->url_imagenexterna = $urlImagenexternal;
        $botonPago->save();


        $title_response = 'successful consult';
        $arr_respuesta = new stdClass();
        $arr_respuesta->success = true;
        $arr_respuesta->titleResponse = $title_response;
        $arr_respuesta->textResponse = $title_response;
        $arr_respuesta->lastAction = $title_response;
        $arr_respuesta->data = $this->getNewData($botonPago);
        $arr_respuesta->data['key'] = $llavesCLiente;

        return (array)$arr_respuesta;
    }
}
