<?php

namespace App\Listeners\Buttons\Process;


use App\Events\Buttons\Process\ConsultButtonListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\BotonesPago;
use App\Models\LlavesClientes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultButtonListListener extends HelperPago
{

    protected $baseUrlPaycoLink = "https://payco.link";

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
    public function handle(ConsultButtonListEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $filters = $fieldValidation["filter"];
            $id = isset($filters->id) ? $filters->id : "";
            $currency = isset($filters->currency) ? $filters->currency : "";
            $reference = isset($filters->reference) ? $filters->reference : "";
            $title = isset($filters->title) ? $filters->title : "";
            $amount = isset($filters->amount) ? $filters->amount : "";
            $searchGeneral = isset($filters->searchGeneral) ? $filters->searchGeneral : false;

            if ($searchGeneral) {
                $qb = DB::table('botones_pago')
                    ->addSelect("descripcion as description")
                    ->addSelect("detalle as detail")
                    ->addSelect("referencia as reference")
                    ->addSelect("moneda as currency")
                    ->addSelect("valor as amount")
                    ->addSelect("tax as iva")
                    ->addSelect("amount_base as vase")
                    ->addSelect("url_respuesta as urlResponse")
                    ->addSelect("url_confirmacion as urlConfirmation")
                    ->addSelect("url_imagen as irlImage")
                    ->addSelect("url_imagenexterna as url_imageExternal")
                    ->addSelect("tipo as type")
                    ->addSelect("id as id")
                    ->orderBy("id", "desc")
                    ->where("id_cliente", $clientId)
                    ->where(function ($query) use ($searchGeneral) {
                        $query->orWhere('Id', intval($searchGeneral))
                            ->orWhere('moneda', $searchGeneral)
                            ->orWhere('referencia', intval($searchGeneral))
                            ->orWhere('descripcion', $searchGeneral)
                            ->orWhere('valor', floatval($searchGeneral));
                    });
                $consult = $qb;
                $consult->orderBy("id", "desc");
                $sellList = $consult->paginate(50);
            } else {
                $sellList = BotonesPago::where("id_cliente", $clientId);
                if ($id != "") {
                    $sellList = $sellList->where("Id", "LIKE", "{$id}%");
                }
                if ($currency != "") {
                    $sellList = $sellList->where("moneda", "LIKE", "{$currency}%");
                }
                if ($reference != "") {
                    $sellList = $sellList->where("referencia", "LIKE", "{$reference}%");
                }
                if ($title != "") {
                    $sellList = $sellList->where("descripcion", "LIKE", "{$title}%");
                }
                if ($amount != "") {
                    $sellList = $sellList->where("valor", "LIKE", "{$amount}%");
                }

                $sellList = $sellList->addSelect("Id as id")
                    ->addSelect("descripcion as description")
                    ->addSelect("detalle as detail")
                    ->addSelect("referencia as reference")
                    ->addSelect("moneda as currency")
                    ->addSelect("valor as amount")
                    ->addSelect("tax as iva")
                    ->addSelect("amount_base as base")
                    ->addSelect("url_respuesta as urlResponse")
                    ->addSelect("url_confirmacion as urlConfirmation")
                    ->addSelect("url_imagen as irlImage")
                    ->addSelect("url_imagenexterna as url_imageExternal")
                    ->addSelect("tipo as type")
                    ->addSelect("id as id")
                    ->orderBy("id", "desc")->paginate(50);
            }

            $llavesCliente = LlavesClientes::where("cliente_id", $clientId)
                ->get()->first();

            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $sellList;

        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar los cobros con los parametros datos";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }

        if(is_array($data)){
            foreach ($data as $item) {
                $item['key'] = $llavesCliente->public_key;
            }
        }
        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['key'] = $llavesCliente->public_key;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
}