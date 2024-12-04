<?php

namespace App\Listeners\ClientValidation;

use App\Events\ClientValidation\ClientValidationQuestionsCifinEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Service\CifinService as Cifin;
use App\Models\Clientes;

class ClientValidationQuestionsCifinListener extends HelperPago
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
    public function handle(ClientValidationQuestionsCifinEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;

            $clientId = $fieldValidation["clientId"];
            $docType=$fieldValidation["docType"];
            $docNumber=$fieldValidation["docNumber"];
            $test=$fieldValidation["test"];
            
            if(isset($test) && $test==true){
                $test=true;
            }else{
                $test=false;
            }

            $arr_respuesta=$fieldValidation;


            $cliente_cifin = Clientes::find($clientId);
            $p_idcustomer = $cliente_cifin->Id;
            $p_key = $cliente_cifin->key_cli;
            $p_tipo = '1';

            $next = true;
            $intentos = 0;


             while ($next) {
            if(!$test){

                $cifin = new Cifin($p_idcustomer, $p_key, $p_tipo);
                $confronta = json_decode($cifin->getConfronta($docType, $docNumber, 'false'));
          
                
            }else{

                         $str_rest = '{"result":{"codigoCuestionario":7025,"descripcionCuestionario":"Verificador Antifaude Online","secuenciaCuestionario":"69010286","claveCIFIN":"","datosTercero":{"codigoTipoIdentificacion":"1","numeroIdentificacion":"1035851980","estadoIdentificacion":"VIGENTE","nombreCompleto":"QUIROZ  SANCHEZ JOSE DANIEL"},"listadoPreguntas":{"item":[{"secuenciaPregunta":"80","textoPregunta":"\u00bfCon qu\u00e9 entidad adquiri\u00f3 un cr\u00e9dito de veh\u00edculo en los \u00faltimos seis meses?","posicionPregunta":"1","listadoRespuestas":{"item":[{"secuenciaPregunta":80,"secuenciaRespuesta":68552083,"textoRespuesta":"JOHN  F.  KENNEDY  LTDA  - COOPERATIVA  DE  AHORRO  Y  CREDI"},{"secuenciaPregunta":80,"secuenciaRespuesta":68552082,"textoRespuesta":"COLTEFINANCIERA"},{"secuenciaPregunta":80,"secuenciaRespuesta":68552084,"textoRespuesta":"FINANCIERA  INTERNACIONAL  S.A"},{"secuenciaPregunta":80,"secuenciaRespuesta":68552085,"textoRespuesta":"Ninguna de las anteriores"}]}},{"secuenciaPregunta":"29","textoPregunta":"En los \u00faltimos seis meses, \u00bfcon cu\u00e1l de las siguientes entidades usted ten\u00eda una tarjeta de cr\u00e9dito y un cr\u00e9dito de vivienda?","posicionPregunta":"2","listadoRespuestas":{"item":[{"secuenciaPregunta":29,"secuenciaRespuesta":68552087,"textoRespuesta":"COLTEFINANCIERA"},{"secuenciaPregunta":29,"secuenciaRespuesta":68552088,"textoRespuesta":"GNB SUDAMERIS"},{"secuenciaPregunta":29,"secuenciaRespuesta":68552086,"textoRespuesta":"INVERSORA PICHINCHA S.A."},{"secuenciaPregunta":29,"secuenciaRespuesta":68552089,"textoRespuesta":"Ninguna de las anteriores"}]}},{"secuenciaPregunta":"28","textoPregunta":"En los \u00faltimos seis meses, \u00bfcon cu\u00e1l de las siguientes entidades usted ten\u00eda una tarjeta de cr\u00e9dito y un cr\u00e9dito de veh\u00edculo?","posicionPregunta":"3","listadoRespuestas":{"item":[{"secuenciaPregunta":28,"secuenciaRespuesta":68552092,"textoRespuesta":"GIROS & FINANZAS"},{"secuenciaPregunta":28,"secuenciaRespuesta":68552091,"textoRespuesta":"BANAGRARIO"},{"secuenciaPregunta":28,"secuenciaRespuesta":68552090,"textoRespuesta":"BANCO FINANDINA"},{"secuenciaPregunta":28,"secuenciaRespuesta":68552093,"textoRespuesta":"Ninguna de las anteriores"}]}},{"secuenciaPregunta":"41","textoPregunta":"La cuota mensual de su cr\u00e9dito de modalidad CR\u00c9DITO DE CONSUMO(ORDINARIO) con DAVIVIENDA est\u00e1 entre:","posicionPregunta":"4","listadoRespuestas":{"item":[{"secuenciaPregunta":41,"secuenciaRespuesta":68552095,"textoRespuesta":"$1 a $300.000"},{"secuenciaPregunta":41,"secuenciaRespuesta":68552096,"textoRespuesta":"$300.001 a $600.000"},{"secuenciaPregunta":41,"secuenciaRespuesta":68552097,"textoRespuesta":"$600.001 a $1.000.000"},{"secuenciaPregunta":41,"secuenciaRespuesta":68552098,"textoRespuesta":"$1.000.001 a $1.500.000"},{"secuenciaPregunta":41,"secuenciaRespuesta":68552099,"textoRespuesta":"MAS DE $1.500.000"},{"secuenciaPregunta":41,"secuenciaRespuesta":68552100,"textoRespuesta":"No tengo ning\u00fan cr\u00e9dito con esta entidad"}]}},{"secuenciaPregunta":"36","textoPregunta":"\u00bfCu\u00e1l es su proveedor de televisi\u00f3n por suscripci\u00f3n?","posicionPregunta":"5","listadoRespuestas":{"item":[{"secuenciaPregunta":36,"secuenciaRespuesta":68552101,"textoRespuesta":"DIRECTV COLOMBIA LTDA"},{"secuenciaPregunta":36,"secuenciaRespuesta":68552102,"textoRespuesta":"TELEDINAMICA   S.A."},{"secuenciaPregunta":36,"secuenciaRespuesta":68552103,"textoRespuesta":"CLARO SOLUCIONES FIJAS TELMEX"},{"secuenciaPregunta":36,"secuenciaRespuesta":68552104,"textoRespuesta":"Ninguna de las anteriores"}]}}]},"huellaConsulta":{"item":{"nombreEntidad":null,"fechaConsulta":null,"cantidadConsultas":null,"resultadoConsulta":null}},"respuestaProceso":{"codigoRespuesta":"1","descripcionRespuesta":"Cuestionario obtenido exitosamente","numeroIntentosTercero":null,"periodoTiempoIntentosTercero":null,"permiteCuestionarioAdicional":null,"cuestionarioBloqueado":null},"codigoTipoCuestionario":2,"cifinlogid":187318,"confrontaid":10688},"success":true}';

                        $confronta = json_decode($str_rest);


            }
   


            if ($confronta->success == true) {

                $success = true;
                $response = "El cuestionario ha sido generado";

                $next = false;

            } else {
                if (isset($confronta->result->respuestaProceso->codigoRespuesta) && $confronta->result->respuestaProceso->codigoRespuesta == "10") {
                    $success = false;
                    $response = $confronta->result->respuestaProceso->descripcionRespuesta;
                    $cuestionario = null;
                    $next = false;
                    break;
                }

                $intentos++;

                if ($intentos > 3) {

                    $success = false;
                    $response = "El cuestionario no ha sido generado, intente nuevamente o contacte al administrador.";
                    $cuestionario = null;
                    $next = false;
                }
            }
        }



            if($success){
                $title_response = 'Success';
            }else{
                $title_response = 'Error';
            }
          
            $text_response = $response;
            $last_action = 'get questions central risk';
            $data = $confronta;

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

}