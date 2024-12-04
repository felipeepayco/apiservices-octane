<?php

namespace App\Listeners\ClientValidation;

use App\Events\ClientValidation\ClientValidationQuestionResponseCifinEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\Clientes;
use App\Models\LimClientesValidacion;
use App\Models\LimConfrontaLog;
use Illuminate\Http\Request;
use App\Service\CifinService as Cifin;


class ClientValidationQuestionResponseCifinListener extends HelperPago
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
     * @param ClientValidationQuestionResponseCifinEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(ClientValidationQuestionResponseCifinEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $codigoCuestionario = $fieldValidation["codigoCuestionario"];
            $secuenciaCuestionario = $fieldValidation["secuenciaCuestionario"];
            $item = $fieldValidation["item"];

            $cliente_cifin = Clientes::where("id", $clientId)->first();
            $p_idcustomer = $cliente_cifin->Id;
            $p_key = $cliente_cifin->key_cli;
            $p_tipo = '1';

            $arr_preguntas = array();

            foreach ($item as $key => $row) {
                $arr_preguntas[$row["secuenciaPregunta"]] = $row["secuenciaRespuesta"];
            }

            $cifin = new Cifin($p_idcustomer, $p_key, $p_tipo);
            $respuesta = $cifin->evaluarconfronta($codigoCuestionario, $secuenciaCuestionario, $arr_preguntas, false);
            //object(stdClass)#938 (6) { ["codigoCuestionario"]=> string(0) "" ["claveCIFIN"]=> string(0) "" ["numeroAciertos"]=> string(1) "4" ["respuestaProceso"]=> object(stdClass)#939 (6) { ["codigoRespuesta"]=> string(1) "1" ["descripcionRespuesta"]=> string(22) "CONFRONTACIÃ“N EXITOSA" ["numeroIntentosTercero"]=> NULL ["periodoTiempoIntentosTercero"]=> NULL ["permiteCuestionarioAdicional"]=> NULL ["cuestionarioBloqueado"]=> string(1) "N" } ["confrontaid"]=> int(10688) ["evaluacionconfrontaid"]=> int(6410) }
            //Consultar si tiene limConFrontLog mayor de 3 intentos
            $arrLimConFrontaLog = LimConfrontaLog::where("cliente_id", $clientId)->where("fecha", ">=", new \DateTime("now"))->get()->toArray();
            $arLimClienteValidacion = LimClientesValidacion::where("cliente_id", $clientId)->where("validacion_id", 2)->first();
            //Insertar los intentos
            if (count($arrLimConFrontaLog) < 3) {
                if (isset($respuesta->numeroAciertos)) {
                    $arLimConFrontaLog = $this->setLimConFrontaLog($respuesta, $clientId);
                    $count = 0;
                    if ($respuesta->numeroAciertos >= 4) {
                        $arLimClienteValidacion->estado_id = 1;
                        $arLimClienteValidacion->save();

                        $success = true;
                        $title_response = "Ok";
                        $text_response = "Identidad validada correctamente";
                        $cod_error = "00";
                    } else {
                        $count = 1;
                        $arLimClienteValidacion->estado_id = 3;
                        $arLimClienteValidacion->save();


                        $success = false;
                        $title_response = "Lo sentimos";
                        $text_response = "Validación de indentidad no exitosa";
                        $cod_error = "01";
                    }

                    $data = ["respuesta" => (array)$respuesta,
                        "fecha" => $arLimConFrontaLog->fecha->format("Y-m-d H:i:s"),
                        "intentos" => count($arrLimConFrontaLog) + $count,
                        "fechaProximoIntento" => $arLimConFrontaLog->fecha_proximo_intento->format("Y-m-d H:i:s"),];
                } else {
                    $success = false;
                    $title_response = "Error";
                    $text_response = "No se pudo validar el cuestionario en linea";
                    $cod_error = "99";
                    $data = [];
                }
            } else {
                $ultimo = end($arrLimConFrontaLog);

                $success = false;
                $title_response = "Error";
                $text_response = "Has superado el numero de intentos validos.";
                $cod_error = "02";
                $data = ["fecha" => $ultimo["fecha"]->format("Y-m-d H:i:s"),
                    "intentos" => count($arrLimConFrontaLog),
                    "fechaProximoIntento" => $ultimo["fechaProximoIntento"]->format("Y-m-d H:i:s")];
            }

            $response = array('success' => $success,
                'title_response' => $title_response,
                'text_response' => $text_response,
                'cod_error' => $cod_error,
                "data" => $data);

            $last_action = 'set questions response central risk';
            $data = $response;

        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error validate data";
            $last_action = 'Internal server error';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }


        $arrResponse['success'] = $success;
        $arrResponse['titleResponse'] = $title_response;
        $arrResponse['textResponse'] = $text_response;
        $arrResponse['lastAction'] = $last_action;
        $arrResponse['data'] = $data;

        return $arrResponse;
    }

    private function setLimConFrontaLog($respuesta, $clientId)
    {
        $fechaActual = new \DateTime("now");
        $fechaProximoIntento = new \DateTime("now");
        $fechaProximoIntento->modify('+1 day');

        $arLimConFrontaLog = new LimConfrontaLog();
        $arLimConFrontaLog->cliente_id = ($clientId);
        $arLimConFrontaLog->aciertos = ($respuesta->numeroAciertos);
        $arLimConFrontaLog->cuestionario_id = ($respuesta->confrontaid);
        $arLimConFrontaLog->respuesta = ($respuesta->respuestaProceso->descripcionRespuesta);
        $arLimConFrontaLog->fecha = ($fechaActual);
        $arLimConFrontaLog->fecha_proximo_intento = ($fechaProximoIntento);
        $arLimConFrontaLog->save();

        return $arLimConFrontaLog;

    }

}