<?php

namespace App\Listeners\PreRegister\Process;

use App\Common\ProductClientStateCodes;
use App\Common\RestrictiveListTypeValidation;
use App\Common\TiposPlanId;
use App\Events\PreRegister\Process\ProcessPreRegisterEvent;
use App\Events\RestrictiveList\Process\ProcessRestrictiveListSaveLogEvent;
use App\Helpers\ClientRegister\HelperClientRegister;
use App\Helpers\Validation\CommonValidation;
use App\Http\Lib\PreRegistroService;
// --para dulpicacion multicuenta
use App\Models\ProductosClientes;
use App\Models\SplitPaymentsClientsApps;
use App\Models\TckMasterDepartamentos;
use Illuminate\Http\Request;
use App\Listeners\Services\MultiAccountService;
use App\Models\Clientes;
//---------------------------
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\PreRegister;
use App\Models\GrantUser;
use App\Models\LimClientesValidacion;
use App\Models\UserCuenta;
use WpOrg\Requests\Requests;

class ProcessPreRegisterListener extends HelperClientRegister
{
    /**
     * @param ProcessPreRegisterEvent $event
     * @return mixed
     * @throws \Exception
     */

    const CLIENT_ID_APIFY_PRIVATE = 4877;
    const ESTADO_ID_LCV = 1;
    const VALIDACION_ID_LCV = 5;

    public function handle(ProcessPreRegisterEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;

            ///Capturar variables
            $document = $fieldValidation['docNumber'];
            $digit = $fieldValidation['digit'];
            $documentType = $fieldValidation['docType'];
            $mobilePhone = $fieldValidation['mobilePhone'];
            $email = $fieldValidation["mail"];
            $gateway = $fieldValidation["gateway"];
            $multiAccount = $fieldValidation["isMultiAccount"];
            $duplicate = isset($fieldValidation["multiAccountDuplicate"]) ? $fieldValidation["multiAccountDuplicate"] : null;
            $duplicateClientId = isset($fieldValidation["multiAccountDuplicateClientId"]) ? $fieldValidation["multiAccountDuplicateClientId"]: null ;

            //si es desde la app o desde la web el registro
            $web = $fieldValidation['web'];

            ///Validar los campos obligatorios

            // servicio para consultar listas restrictivas
            $dataToSearch = [
                'docType' => $documentType,
                'docNumber' => $document,
                'digit' => $digit,
            ];
            $restrictiveList = $this->restrictiveList($dataToSearch);

            if (isset($restrictiveList['error']) && $restrictiveList['error'] === true) {
                // si el servicio no responde, no se hace el registro
                return [
                    'success' => $restrictiveList['success'],
                    'titleResponse' => $restrictiveList['titleResponse'],
                    'textResponse' => $restrictiveList['textResponse'],
                    'lastAction' => $restrictiveList['lastAction'],
                    'data' => $restrictiveList['data'],
                ];
            }

            $isInRestrictiveList = $restrictiveList['succes'] ?? false;

            ///Verificar numero de cuenta daviplata
            $shortNumber = substr($mobilePhone, -4);
            $accountEncrypt = false;
            $dataAccount = $accountEncrypt ? ["shortNumber" => $shortNumber, "accountEncrypt" => $accountEncrypt] : "";

            $dateNow = new \DateTime('now');

            ///Guardar el preRegistro
            ///
            /// Si es multicuenta no va e instanciar el preRegister con los datos de la peticion o la cuenta duplicada
            $preRegister = $this->savePreRegister($fieldValidation, $isInRestrictiveList, $dateNow);

            if (!$preRegister) {
                return [
                    'success' => false,
                    'titleResponse' => trans("message.Client don't registered"),
                    'textResponse' => trans("message.Error validation"),
                    'lastAction' => 'pre-register',
                    'data' => [],
                ];
            }

            event(new ProcessRestrictiveListSaveLogEvent(
                $preRegister,
                $dataToSearch,
                $restrictiveList,
                $isInRestrictiveList,
                RestrictiveListTypeValidation::REGISTER
            ));

            $isEntityAllied = $gateway && $preRegister->id_cliente_entidad_aliada !== self::CLIENT_ID_APIFY_PRIVATE;
            //Crear el cliente
            if ($multiAccount || $isEntityAllied) {

                $resultClient = $this->saveClient($preRegister, $dataAccount, $fieldValidation);

                if ($resultClient["status"]) {
                    $data = [];
                    $this->data($preRegister, $email, $fieldValidation, $resultClient, $data);
                    $success = true;
                    $title_response = trans("message.Register success");
                    $text_response = trans("message.Thanks for you register.");
                    $last_action = "pre-register";
                } else {
                    $validate = new Validate();
                    $validate->getErrorCheckout("A007");
                    $success = false;
                    $title_response = $resultClient["message"];
                    $text_response = trans("message.Error validation");
                    $last_action = "created-client";
                    $data = ['totalErrors' => $validate->totalerrors, 'errors' => $validate->errorMessage];
                }


                if($multiAccount){
                    if($duplicate){
                        $client = Clientes::where('id', $preRegister->cliente_id)->first();

                        $multiAccountService = new MultiAccountService();
                        $multiAccountService->duplicateAccount($client->Id, $duplicateClientId);

                        $data["client"]["validation"] = LimClientesValidacion::where("cliente_id", $client->Id)
                            ->where("estado_id", self::ESTADO_ID_LCV)
                            ->where("validacion_id", "<=", self::VALIDACION_ID_LCV)
                            ->sum("porcentaje");
                    }

                    $this->sendEmail($preRegister, $multiAccount);
                }

            } else {
                if ($web === true) {
                    if ($preRegister->id_cliente_entidad_aliada === self::CLIENT_ID_APIFY_PRIVATE) {
                        $this->sendEmailAggregator($preRegister, $gateway);
                    }

                    $success = true;
                    $title_response = trans("message.Register success");
                    $text_response = trans("message.Thanks for you register.");
                    $last_action = "pre-register";
                    $data = [
                        'idPreregister' => $preRegister->id,
                        'confirmToken' => $preRegister->token,
                    ];
                } else {

                    $this->sendEmail($preRegister);

                    $success = true;
                    $title_response = trans("message.Register success");
                    $text_response = trans("message.Thanks for you register.");
                    $last_action = "pre-register";
                    $data = ["token" => $preRegister->token, "url" => "app://epayco/validate/email?token={$preRegister->token}"];
                }
            }
        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = $exception->getMessage();
            $last_action = $exception->getCode();
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' => $validate->errorMessage);
        }

        $arrResponse['success'] = $success;
        $arrResponse['titleResponse'] = $title_response;
        $arrResponse['textResponse'] = $text_response;
        $arrResponse['lastAction'] = $last_action;
        $arrResponse['data'] = $data;

        return $arrResponse;
    }

    /**
     * @param $data
     * @param $validate
     * @param $dateNow
     * @return PreRegister
     */
    private function savePreRegister($data, $validateRestrictiveUser, $dateNow)
    {
        $preRegister = new PreRegister();
        $preRegister->cel_number = isset($data["mobilePhone"]) ? $data["mobilePhone"] : null;
        $preRegister->country = $data["country"];
        $preRegister->doc_number = $data["docNumber"];
        $preRegister->doc_type = $data["docType"];
        $preRegister->email = $data["mail"];
        $preRegister->names = isset($data["firstNames"]) ? $data["firstNames"] : "";
        $preRegister->surnames = isset($data["lastNames"]) ? $data["lastNames"] : "";
        $preRegister->user_type = $data["userType"];
        $preRegister->created_at = $dateNow;
        $preRegister->restricted_user = $validateRestrictiveUser;
        $preRegister->digito = isset($data["digit"]) ? $data["digit"] : null;
        $preRegister->nombre_empresa = isset($data["companyName"]) ? $data["companyName"] : "";
        $preRegister->alianza_id = isset($data["alianzaId"]) ? $data["alianzaId"] : 1;
        $preRegister->password_jwt = $data["password"];

        $preRegister->category = isset($data["category"]) ? $data["category"] : null;
        $preRegister->subcategory = isset($data["subcategory"]) ? $data["subcategory"] : null;

        if (isset($data['entityAlliedIsPlus']) && $data['entityAlliedIsPlus'] === true) {
            $preRegister->id_cliente_entidad_aliada = $data['entityAlliedIsPlus'] === true ? $data['clientId'] : self::CLIENT_ID_APIFY_PRIVATE;
        } else {
            $preRegister->id_cliente_entidad_aliada = self::CLIENT_ID_APIFY_PRIVATE;
            $preRegister->id_aliado = $data['clientId'] !== self::CLIENT_ID_APIFY_PRIVATE ? $data['clientId'] : null;
        }


        $data["metaTag"] = "Apify";
        if (isset($data["prefijo"])) {
            $preRegister->prefijo = $data["prefijo"];
        }
        if (isset($data["referenceId"])) {
            $preRegister->id_aliado = $data["referenceId"];
        }
        if (isset($data["metaTag"])) {
            $preRegister->meta_tag = $data["metaTag"];
        }
        if (isset($data["utmSource"])) {
            $preRegister->utm_source = $data["utmSource"];
        }
        if (isset($data["utmMedium"])) {
            $preRegister->utm_medium = $data["utmMedium"];
        }
        if (isset($data["utmCompaign"])) {
            $preRegister->utm_compaign = $data["utmCompaign"];
        }
        if (isset($data["utmContent"])) {
            $preRegister->utm_content = $data["utmContent"];
        }
        if (isset($data["utmTerm"])) {
            $preRegister->utm_term = $data["utmTerm"];
        }

        if (isset($data["mobilePhone"])) {
            $preRegister->cel_number = $data["mobilePhone"];
        }
        if (isset($data["gateway"])) {
            $preRegister->plan_id = $data["gateway"] ? 1011 : 1010;
            $preRegister->proforma = isset($data["proforma"]) ? $data["proforma"] : true;
        }

        //Validar si el registro es con redes sociale
        if (isset($data["socialNetwork"]) && isset($data["idSocialNetwork"])) {
            $preRegister->social_network = $data["socialNetwork"];
            $preRegister->id_social_network = $data["idSocialNetwork"];
        }

        $strToke = $dateNow->format("Y-m-d H:i:s") . '' . $preRegister->doc_number . '' . $preRegister->doc_type;
        $token = md5($strToke);

        $preRegister->token = $token;
        $preRegister->request = json_encode($data);

        $preRegister->save();

        return $preRegister;
    }

    /**
     * Función para crear el cliente y grant_user
     * @param $preRegister PreRegister
     * @param $dataAccount
     * @param null $register
     * @return array
     * @throws \Exception
     */
    public function saveClient(&$preRegister, $dataAccount, $register)
    {
        if ($preRegister) {
            try {
                $preRegistroService = new PreRegistroService();
                $preRegistroService->request = $this->request;

                if ($preRegister->user_type == "persona") {
                    $registerResponse = $preRegistroService->registerUser($preRegister, $register["password"]);
                } else {
                    $registerResponse = $preRegistroService->registerUserComerce($preRegister, $register["password"], $register);
                }

                if (true === $registerResponse['success']) {


                    $client = $registerResponse['typeUser'] === 'gateway'
                        ? $registerResponse['data']['comercio']
                        : $registerResponse['data'];

                    //agregamos el producto de paypal a todos los usuarios que se registren.
                    $this->createProductToClient($client->Id,getenv('PRODUCT_ID_PAYPAL_PAYMENTS'));
                    //validamos si es usuario agregador para agregarlo a la tabla splitPaymentClientesApp
                    if($preRegister->plan_id == TiposPlanId::AGREGADOR){
                        $dateNow = new \DateTime('now');
                        $clientSplitPayment = new SplitPaymentsClientsApps();
                        $clientSplitPayment->fecha = $dateNow->format("Y-m-d H:i:s");
                        $clientSplitPayment->clienteapp_id = $client->Id;
                        $clientSplitPayment->estado = 1;
                        $clientSplitPayment->save();

                        $this->createProductToClient($client->Id,getenv('PRODUCT_ID_SPLIT_PAYMENT'));
                    }
                    //si es multicuenta no va la creacion de grant user
                    if (!$register["isMultiAccount"]) {
                        $grantUserResponse = $this->setGrantUser($preRegister);
                        if ($grantUserResponse['status'] === true) {
                            $grantUser = $grantUserResponse['grantUser'];
                            $grantUser->cliente_id = $client->Id;
                            $grantUser->save();
                            $preRegistroService->addUserAccount(
                                $client->Id,
                                $grantUser->id,
                                $client->nombre_empresa,
                                CommonValidation::validateIsSet($register, "multiAccountDuplicateClientId", null)
                            );
                        } else {
                            $client->delete();
                            return $grantUserResponse;
                        }
                    } else {
                        $preRegistroService->addUserAccount(
                            $client->Id,
                            $register["multiAccountGrantUserId"],
                            $register["multiAccountName"],
                            CommonValidation::validateIsSet($register, "multiAccountDuplicateClientId", null)
                        );
                    }

                    $this->updateClient($client, $preRegister, $dataAccount);

                } else {
                    // si hay error al crear el cliente y el usuario es gateway se eliminara tambien el preRegistro
                    if ($registerResponse['typeUser'] === 'gateway') {
                        $preRegister->delete();
                    }
                    return ["message" => trans("Client don't created"), "status" => false, "data" => []];
                }
            } catch (\Exception $exception) {
                return ["message" => trans("Client don't created"), "status" => false, "data" => []];
            }
            return ["message" => trans("message.Client created successfully"), "status" => true, "data" => isset($client) ? ($preRegister->plan_id == 1011 ? $registerResponse["data"]["data"] : []) : []];
        }
        return ["message" => trans("message.Client don't registered"), "status" => false, "data" => []];
    }

    /**
     * @param $preRegister PreRegister
     * @return array
     */
    public function sendEmail($preRegister, $multiAccount = false)
    {
        // Guardar en preRegistro los datos enviados en estre primer request.
        $name = $preRegister->names . ' ' . $preRegister->surnames;
        if ($preRegister->nombre_empresa != "") {
            $name = $preRegister->nombre_empresa;
        }

        if ($multiAccount) {
            $userAccount = UserCuenta::where('cliente_id', $preRegister->cliente_id)->first();
            $grantUserData = GrantUser::where('id', $userAccount->grant_user_id)->first();
            $grantUserName = $grantUserData->nombres . ' ' . $grantUserData->apellidos;

            $this->emailPanelRest("Creación de multicuenta", $preRegister->email, "multi_account_creation", ["nameGrantUser" => $grantUserName, "multiAccountName" => $userAccount->nombre_cuenta]);
        } else {
            //Enviar email
            return $this->emailPanelRest("Validar correo", $preRegister->email, "activar_cliente", ["usuario" => $name, "email" => "$preRegister->email", "token" => $preRegister->token, "url" => "appepayco://epayco/validate/email"]);
        }
    }


    private function sendEmailAggregator(&$preRegister, $isGateway)
    {
        $token = $preRegister->token;
        $email = $preRegister->email;
        $name = $preRegister->nombre_empresa != ''
            ? $preRegister->nombre_empresa
            : $preRegister->names . ' ' . $preRegister->surnames;

        $baseDashboard = getenv('DASHBOARD_URL');
        $baseUrlRest = getenv('BASE_URL_REST');
        $baseEntornoPanelRest = getenv('BASE_URL_APP_REST_ENTORNO');

        $urlRegisterEmail = "{$baseDashboard}/api/registro/crear/clienteApify/{$token}";
        $preRegister->url_validate = $urlRegisterEmail;
        $preRegister->save();
        $base64 = base64_encode("{$email};;{$name};;{$urlRegisterEmail};;true;;$isGateway"); //Convertir variables en base64
        $urlServiceEmail = "{$baseUrlRest}/{$baseEntornoPanelRest}/email/preregistro/{$base64}";

        try {
            $headers = ['Accept' => 'application/json', "Content-Type" => "application/json"];
            return Requests::get($urlServiceEmail, $headers, ["timeout" => 120]);
        } catch (\Exception $exception) {
            return $exception;
        }
    }

    private function createProductToClient($clientId, $productId){

        $now = date("d-m-Y");

        $clientProduct = new ProductosClientes();
        $clientProduct->fecha_creacion = new \DateTime($now);
        $clientProduct->fecha_inicio = new \DateTime($now);
        $clientProduct->fecha_periodo = null;
        $clientProduct->fecha_renovacion =new \DateTime(date("d-m-Y",strtotime($now."+ 1 month")));
        $clientProduct->fecha_cancelacion = null;
        $clientProduct->estado = ProductClientStateCodes::ACTIVE;
        $clientProduct->cliente_id = $clientId;
        $clientProduct->producto_id = $productId;
        $clientProduct->periocidad = ProductClientStateCodes::ACTIVE;
        $clientProduct->observations = null;
        $clientProduct->facturar_a_cliente_id = null;

        $clientProduct->save();

        return true;
    }
}
