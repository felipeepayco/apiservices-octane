<?php

namespace App\Listeners;


use App\Events\PayPalAssociateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Lib\PaypalService;
use App\Http\Validation\Validate as Validate;
use App\Models\Clientes;
use App\Models\PaypalClientes;
use App\Models\PaypalRetiros;
use Illuminate\Http\Request;

class PayPalAssociateListener extends HelperPago
{

    private $urlService = "apprest/email/vinculate/desvinculate/paypal";

    /**
     * CatalogueNewListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param PayPalAssociateEvent $event
     * @return mixed
     */
    public function handle(PayPalAssociateEvent $event)
    {

        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $code = $fieldValidation["code"];

            $paypalServices = new PaypalService();

            $paypalClient = PaypalClientes::where('cliente_id', $clientId)->first();
            if ($paypalClient) {
                $paypalClient->last_code = $code;
            } else {
                $paypalClient = new PaypalClientes();
                $paypalClient->last_code = $code;
                $paypalClient->cliente_id = $clientId;
            }

            ///Consultar el initialToken
            $initialInfo = $paypalServices->getInitialToken('withdraws');
            $initialInfo = json_decode($initialInfo);
            $paypalClient->initial_token_bearer = $initialInfo->access_token;
            $paypalClient->refresh_token = isset($initialInfo->refresh_token) ? $initialInfo->refresh_token : "R23AAHEKsnLlE9dNMU3y7Pn51WKU8I5of-azxz8PuLokc7e7T82Plfy6UyXsTnFjodR0qB_OG2XVyKz_3Nsfxi6EVai5-_n9p0cHXWmv8Ajn-qPgfAbmsS3Jjcl_iAlt3xuXEOkMQ-uzU-3GsZZSA";

            ///Consultar el userToken
            $userToken = $paypalServices->getUserToken($paypalClient->last_code, $paypalClient->initial_token_bearer);
            $userToken = json_decode($userToken);

            if (isset($userToken->access_token)) {
                $paypalClient->customer_access_token = $userToken->access_token;

                ////Consultar la información de PayPal9
                $userPaypalInfo = $paypalServices->getUserInfo($paypalClient->customer_access_token);
                $userPaypalInfo = json_decode($userPaypalInfo);
                $paypalClient->paypal_user_id = $userPaypalInfo->user_id;
                $paypalClient->name = $userPaypalInfo->name;
                $paypalClient->email = $userPaypalInfo->emails[0]->value;
                $paypalClient->paypal_user_info_response = (string)json_encode($userPaypalInfo);

                ///Consultar el saldo de PayPal
                $userBalance = $paypalServices->getUserBalance($paypalClient->customer_access_token);
                $userBalance = json_decode($userBalance);
                $paypalClient->user_balance = $userBalance->total_available->value;
                $paypalClient->moneda_balance = $userBalance->total_available->currency_code;


                ///Validar el correo primario y secundario
                $arClient = Clientes::where('id', $clientId)->first();
                $emailMatch = true;
                $emailMatchVerified = true;
//                $this->validateEmail($userPaypalInfo, $emailMatch, $emailMatchVerified, $arClient->email,$arClient->Id);

                if ($emailMatch && $emailMatchVerified) {

                    $paypalClient->save();

                    ///Enviar correo de vinculación PayPal Retiros
                    //Servicio para el envio del correo
                    $this->sendEmail($arClient, "activeWithdrawal");

                    $arPayPalRetiros = PaypalRetiros::where("id_cliente", $clientId)->where("status", 0)->get();
                    $paypalPending = 0;
                    foreach ($arPayPalRetiros as $arPayPalRetiro) {
                        $paypalPending += $arPayPalRetiro->total_amount_received;
                    }


                    $success = true;
                    $desc = "PayPal associated";
                    $title_response = "Successful {$desc} PayPal";
                    $text_response = "successful {$desc} PayPal";
                    $last_action = "paypal_{$desc}";
                    $data = [
                        "balance" => [
                            'available' => $paypalClient->user_balance,
                            'pending' => $paypalPending,
                            'total' => $paypalClient->user_balance,
                            'currency' => $paypalClient->moneda_balance
                        ],
                        "associated" => true,
                    ];


                } else {
                    $error = !$emailMatch ? "PayPal email does not match registered email" : (!$emailMatchVerified ? "PayPal email is not verified" : "");
                    $success = false;
                    $desc = "PayPal not associated, {$error}";
                    $title_response = "Error {$desc} paypal";
                    $text_response = "Error {$desc} paypal";
                    $last_action = "PayPal_{$desc}";
                    $data = [
                        "associated" => false,
                        "emailMatch" => $emailMatch,
                        "emailMatchVerified" => $emailMatchVerified
                    ];
                }

            } else {
                $success = false;
                $desc = $userToken->error;
                $title_response = "Error {$desc} PayPal";
                $text_response = "Error {$desc} PayPal";
                $last_action = "paypal_{$desc}";
                $data = [
                    "associated" => false,
                    "error" => $userToken
                ];
            }
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error create new catalogue";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                "associated" => false,
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }

        $arrResponse['success'] = $success;
        $arrResponse['titleResponse'] = $title_response;
        $arrResponse['textResponse'] = $text_response;
        $arrResponse['lastAction'] = $last_action;
        $arrResponse['data'] = $data;

        return $arrResponse;
    }

    private function sendEmail($arClient, $type)
    {
        if ($arClient) {
            $data = ["name" => $arClient->razon_social, "type" => $type, "email" => $arClient->email];
            $baseUrl = env("base_url_rest");
            $response = $this->sendCurlVariables($baseUrl . "/" . $this->urlService, $data, "POST", true);
        }
    }

    private function validateEmail($userPaypalInfo, &$emailMatch, &$emailMatchVerified, $emailClient, $clienteId)
    {

        $em = $this->getDoctrine()->getManager();

        $arrWithdrawalsPaypalClient = $em->getRepository("App\\Entity\\PaypalRetiros")->findOneBySomeField($clienteId);
        if ($arrWithdrawalsPaypalClient) {
            $validateEmailPrimary = (boolean)$arrWithdrawalsPaypalClient[0]["validar_correo_principal"];
            $validateEmailPrimaryVerified = (boolean)$arrWithdrawalsPaypalClient[0]["validar_correo_principal_verificado"];
            $validateEmailSecundary = (boolean)$arrWithdrawalsPaypalClient[0]["validar_correo_secundario"];
            $validateEmailsecundaryVerified = (boolean)$arrWithdrawalsPaypalClient[0]["validar_correo_secundario_verificado"];
        } else {
            $emailMatch = true;
            $emailMatchVerified = true;
            return;
        }

        foreach ($userPaypalInfo->emails as $email) {
            if ($validateEmailPrimary) {
                if ($email->value == $emailClient && $email->primary) {
                    $emailMatch = true;
                }
            }
            if ($validateEmailSecundary) {
                if ($email->value == $emailClient && !$email->primary) {
                    $emailMatch = true;
                }
            }

            if ($validateEmailPrimaryVerified) {
                if ($email->confirmed && $email->primary) {
                    $emailMatchVerified = true;
                }
            }
            if ($validateEmailsecundaryVerified && !$email->primary) {
                if ($email->confirmed) {
                    $emailMatchVerified = true;
                }
            }
        }

        if (!$emailMatch && !$validateEmailPrimary) {
            $emailMatch = true;
        }

        if (!$emailMatchVerified && !$validateEmailPrimaryVerified) {
            $emailMatchVerified = true;
        }

        if (!$validateEmailPrimary && !$validateEmailSecundary) {
            $emailMatch = true;
        }

        if (!$validateEmailPrimaryVerified && !$validateEmailsecundaryVerified) {
            $emailMatchVerified = true;
        }

    }
}