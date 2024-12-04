<?php

namespace App\Listeners\ClientValidation;

use App\Events\ClientValidation\ClientValidationCifinResponseEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;


class ClientValidationCifinResponseListener extends HelperPago
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
     * @param ClientValidationCifinResponseEvent $event
     * @return mixed
     */
    public function handle(ClientValidationCifinResponseEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            $data = $event->arr_parametros;

            $clientId = $fieldValidation["clientId"];
            $docType = $fieldValidation["docType"];
            $docNumber = $fieldValidation["docNumber"];

            $validate = new Validate();
            $arr_respuesta = array();

            if (isset($clientId)) {
                $vclientId = $validate->ValidateVacio($clientId, 'cliente Id');
                if (!$vclientId) {
                    $validate->setError(500, "field clienteId is required");
                } else {
                    $arr_respuesta['clienteId'] = $clientId;
                }
            } else {
                $validate->setError(500, "field clientId is required");
            }

            if (isset($docType)) {
                $vdocType = $validate->ValidateVacio($docType, 'docType');
                if (!$vdocType) {
                    $validate->setError(500, "field docType is required");
                } else {
                    $arr_respuesta['docType'] = $docType;
                }
            } else {
                $validate->setError(500, "field docType is required");
            }

            if (isset($docNumber)) {
                $vdocNumber = $validate->ValidateVacio($docNumber, 'docNumber');
                if (!$vdocNumber) {
                    $validate->setError(500, "field docNumber is required");
                } else {
                    $arr_respuesta['docNumber'] = $docNumber;
                }
            } else {
                $validate->setError(500, "field docNumber is required");
            }

            //// item ///////////
            if (isset($data['item'])) {
                $item = $data['item'];
            } else {
                $item = false;
            }
            if (isset($item)) {
                if (is_array($item)) {
                    if (count($item) >= 5) {
                        $arrResponse['item'] = (array)$item;
                    } else {
                        $validate->setError(500, "field item is not complete");
                    }
                } else {
                    $validate->setError(500, "field item is type array");
                }
            } else {
                $validate->setError(500, "field item required");
            }
            ///// item ///////////

            //// codigoCuestionario ///////////
            if (isset($data['codigoCuestionario'])) {
                $codigoCuestionario = $data['codigoCuestionario'];
            } else {
                $codigoCuestionario = false;
            }
            if (isset($codigoCuestionario)) {
                $vcodigoCuestionario = $validate->ValidateVacio($codigoCuestionario, 'codigoCuestionario');
                if (!$vcodigoCuestionario) {
                    $validate->setError(500, "field codigoCuestionario required");
                } else {
                    if (is_integer($codigoCuestionario)) {
                        $arrResponse['codigoCuestionario'] = (int)$codigoCuestionario;
                    } else {
                        $validate->setError(500, "field codigoCuestionario is type integer");
                    }
                }
            } else {
                $validate->setError(500, "field codigoCuestionario required");
            }
            ///// codigoCuestionario ///////////

            //// secuenciaCuestionario ///////////
            if (isset($data['secuenciaCuestionario'])) {
                $secuenciaCuestionario = $data['secuenciaCuestionario'];
            } else {
                $secuenciaCuestionario = false;
            }
            if (isset($secuenciaCuestionario)) {
                $vsecuenciaCuestionario = $validate->ValidateVacio($secuenciaCuestionario, 'secuenciaCuestionario');
                if (!$vsecuenciaCuestionario) {
                    $validate->setError(500, "field secuenciaCuestionario required");
                } else {
                    if (is_integer($secuenciaCuestionario)) {
                        $arrResponse['secuenciaCuestionario'] = (int)$secuenciaCuestionario;
                    } else {
                        $validate->setError(500, "field secuenciaCuestionario is type integer");
                    }
                }
            } else {
                $validate->setError(500, "field secuenciaCuestionario required");
            }
            ///// secuenciaCuestionario ///////////

            if ($validate->totalerrors > 0) {
                $success = false;
                $title_response = 'Error';
                $text_response = "Error invalid params";
                $last_action = 'validate inputs';
                $data = $validate;
            } else {

                $success = true;
                $title_response = 'Success token generate';
                $text_response = "Success token generate";
                $last_action = 'validate inputs';
                $data = $arr_respuesta;
            }


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