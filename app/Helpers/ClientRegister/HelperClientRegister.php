<?php


namespace App\Helpers\ClientRegister;

use App\Common\RiskTypeClient;
use App\Helpers\Pago\HelperPago;
use App\Http\Lib\ConsultControlListDaviviendaService;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate;
use App\Models\ApifyClientes;
use App\Models\Clientes;
use App\Models\ClientesAplicarTarifa;
use App\Models\ClientesRegistroTarifa;
use App\Models\ClientesTerminos;
use App\Models\ClientesTipoRiesgo;
use App\Models\ClientsRestrictiveList;
use App\Models\ComisionClienteAliado;
use App\Models\ConfiguracionAlianzaAliados;
use App\Models\ConfPais;
use App\Models\ContactosClientes;
use App\Models\CuentasBancarias;
use App\Models\DetalleClientes;
use App\Models\DetalleConfClientes;
use App\Models\GrantUser;
use App\Models\GrantUserOauth;
use App\Models\LimEmailSms;
use App\Models\LlavesClientes;
use App\Models\MediosPagoClientes;
use App\Models\PreRegister;
use App\Models\ProductosClientes;
use App\Models\SaldoAliados;
use App\Models\TerminosEpayco;
use Illuminate\Support\Facades\DB;
use WpOrg\Requests\Requests;

class HelperClientRegister extends HelperPago
{

    /**
     * @param bool $status
     * @param string $titleResponse
     * @param string $textResponse
     * @param string $lastAction
     * @param int $code
     * @param array|null $data
     * @return \Illuminate\Http\JsonResponse
     */

    const COLOMBIA = "CO"; // id de conf pais

    protected function createResponse(
        bool $status,
        string $titleResponse,
        string $textResponse,
        string $lastAction,
        int $code = 200,
        array $data = null
    ): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => $status,
            'titleResponse' => $titleResponse,
            'textResponse' => $textResponse,
            'lastAction' => $lastAction,
            'data' => [],
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * @param int $clientId
     * @return mixed
     */
    protected function alliedEntityValidation(int $clientId)
    {
        //validacion: cliente tiene el producto entidad aliada para poder registrar usuarios
        return ProductosClientes::where('cliente_id', $clientId)->where('estado', 1)
            ->whereHas('product', function ($query) {
                $query->where('tipo_plan', 15);
            })->first();
    }

    /**
     * @param string $email
     * @param int $clientId
     * @param bool $entityAlliedIsPlus
     * @param bool $isMultiAccount
     * @return bool
     */
    protected function existsClient(string $email, int $clientId, bool $entityAlliedIsPlus, bool $isMultiAccount = false): bool
    {
        //Validar que el email no se encuentre en uso con la entidad aliada que hace el pre_registro
        //si la entidad aliada el tipo de producto es estandar, se debe validar que no exista una cuenta de epayco
        $preRegister = PreRegister::where('email', $email)
            ->where('id_cliente_entidad_aliada', $entityAlliedIsPlus === true ? $clientId : 4877)
            ->first();
        $grantUser = GrantUser::where('email', $email)
            ->where('id_cliente_entidad_aliada', $entityAlliedIsPlus === true ? $clientId : 4877)
            ->first();

        if (($preRegister || $grantUser) && !$isMultiAccount) {
            return true;
        }

        return false;
    }

    /**
     * @param array $data
     * info data : [(int)docNumber, (string)docType, (int)digit]
     * @return array
     */
    protected function restrictiveList(array $data)
    {
        $documentType = $data['docType'];
        $document = $data['docNumber'];
        $digit = $data['digit'];

        $consultControlListDavivienda = new ConsultControlListDaviviendaService();
        $documentToValidate = $documentType == 'NIT' && $digit !== null ? $document . $digit : $document;
        $validateRestrictiveList = $consultControlListDavivienda->consultControlListDaviviendaService($documentToValidate, $documentType);

        // Verificar si el servicio de davivienda responde satisfactoriamente
        $validate = new Validate();

        if (!isset($validateRestrictiveList->succes)) {
            $validate->getErrorCheckout("A006");
            $response['success'] = false;
            $response['error'] = true;
            $response['titleResponse'] = trans("message.Service Davivienda does not work");
            $response['textResponse'] = trans("message.The restrictive list service does not respond");
            $response['lastAction'] = "restrictive-list";
            $response['data'] = ['totalErrors' => $validate->totalerrors, 'errors' => $validate->errorMessage];

            return $response;
        }

        return (array) $validateRestrictiveList;
    }

    /**
     * @param $preRegister
     * @return array
     */
    protected function setGrantUser(PreRegister $preRegister): array
    {
        $response = [];

        try {
            $user = new GrantUser();
            $name = $preRegister->names;
            if ($preRegister->nombre_empresa != "") {
                $name = $preRegister->nombre_empresa;
            }
            //concatenando id de Entidad aliada que hace el registro, debido a restricciones de la tabla y libreria FOS user de symfony
            $idPartnerEntity =
                isset($preRegister->id_cliente_entidad_aliada)
                    ? $preRegister->id_cliente_entidad_aliada
                    : 4877;
            $explodeEmail = explode('@', $preRegister->email);
            $concatenatedEmail = $explodeEmail[0] . '.' . $idPartnerEntity . '@' . $explodeEmail[1];


            $user->nombres = $name;
            $user->apellidos = $preRegister->surnames;
            $user->username = $preRegister->email;
            $user->username_canonical = $concatenatedEmail;
            $user->email_canonical = $concatenatedEmail;
            $user->email = $preRegister->email;
            $user->enabled = true;
            $user->roles = serialize(array('ROLE_ADMIN'));
            $user->id_cliente_entidad_aliada = $idPartnerEntity;
            $user->password = $preRegister->password_jwt;
            $user->save();

            $response['status'] = true;
            $response['grantUser'] = $user;
            return $response;
        } catch (\Exception $exception) {
            $response['message'] = trans("Client don't created");
            $response['status'] = false;
            return $response;
        }
    }

    /**
     * @param Clientes $client
     * @param PreRegister $preRegister
     * @param bool $isRegisterExpress
     */
    protected function updateClient(
        Clientes &$client,
        PreRegister &$preRegister,
        $dataAccount = null,
        $isRegisterExpress = false
    )
    {
        

        $preRegister->cliente_id = $client->Id;
        $preRegister->email_verified = 1;

        ///Pendiente verificar el aliado
        if ($preRegister->id_aliado) {
            //Consumir crear aliado
            $arClienteAliado = Clientes::where('id', $preRegister->id_aliado)->first();
            if ($arClienteAliado) {
                $arClienteAliado->aliado = 1;
                $arClienteAliado->save();
            }
            $client->id_aliado = $preRegister->id_aliado;
            //crear alido y comisiones
            $this->createAllied($client, $preRegister);

            // tarifa
            $this->saveClientTariff($client);
        }
        //insertamos los limites a 5
//            $this->updateLimValidation($client->Id, 6);

        ///Creamos la cuenta bancaria
        if ($dataAccount) {
            $this->createAccountBank($client->Id, $dataAccount, "DP");
        }

        if ($preRegister->id_social_network && $preRegister->social_network) {
            $this->setGrantUSerOauth($preRegister, $client);
        }

        // Creamos el registro del apifyClientes
        $this->saveApifyServiceClient($client, $preRegister);

        $client->id_estado = 1;
        $client->save();

        // terminos y condiciones
        $this->saveClientTerms($preRegister, $isRegisterExpress, $client->id_pais);

        //agregar lim_email_sms para agregadores
        if ($preRegister->plan_id == 1010) {
            $this->saveLimEmailSms($client->Id);
        }

        //actualizar info en listas restrictivas
        $this->updateRestrictiveClientList($preRegister);

        // segun config de entidad aliada
        $confEntityAllied = DetalleConfClientes::where('cliente_id', $preRegister->id_cliente_entidad_aliada)
            ->where('config_id', 50)
            ->first();


        

        if ($confEntityAllied && $preRegister->id_cliente_entidad_aliada !== 4877 && $preRegister->plan_id == 1011) {
            
            $conf = $confEntityAllied->valor;
            $arrayConf = json_decode($conf, true);
            if (isset($arrayConf['productos']) && is_array($arrayConf['productos'])) {
                // insertar los mismos productos de la entidad aliada
                $this->sameProductsEntityAllied($client, $arrayConf['productos']);
            }

            if (isset($arrayConf['mediosPago']) && $arrayConf['mediosPago'] === true) {
                // insertar los mismos medios de pago de la entidad aliada
                $this->samePaymentMethodsEntityAllied($client, $preRegister->id_cliente_entidad_aliada);
            }

            if (isset($arrayConf['emailTransacciones']) && $arrayConf['emailTransacciones'] != "") {
                // Modificar configuración de email de transacciones, para que no envie por el default del cliente sino por el de la configuración de entidad aliada
                $arDetailConfClients = DetalleConfClientes::where("cliente_id", $client->Id)->where("config_id", 4)->first();
                if (!$arDetailConfClients) {
                    $arDetailConfClients = new DetalleConfClientes();
                    $arDetailConfClients->cliente_id = $client->Id;
                }
                $arDetailConfClients->valor = $arrayConf['emailTransacciones'];
                $arDetailConfClients->save();
            }
        }


        if($confEntityAllied){
            $conf = $confEntityAllied->valor;
            $arrayConf = json_decode($conf, true);

            if ($confEntityAllied && $preRegister->id_cliente_entidad_aliada !== 4877 && isset($arrayConf['publicidad_epayco']) && !$arrayConf['publicidad_epayco']) {
                
                $confPublicidad = 49;
                $arDetailConfClients = new DetalleConfClientes();
                $arDetailConfClients->cliente_id = $client->Id;
                $arDetailConfClients->config_id = $confPublicidad;
                $arDetailConfClients->valor = 0;
                $arDetailConfClients->save();

            }
        }


        
    }

    /**
     * @param Clientes $client
     * @param PreRegister $preRegister
     */
    private function createAllied(Clientes $client, PreRegister $preRegister)
    {
        $client->id_aliado = $preRegister->id_aliado;
        $client->aliado = 0;

        if ($preRegister->alianza_id == 2) {
            $client->aliado = 1;
        }
        $client->save();

        //Insertar saldos del aliado segun la configuracion
        $confAllied = ConfiguracionAlianzaAliados::find($preRegister->alianza_id);
        if (!$confAllied) {
            return;
        }

        //enviar email
        $headers = ['Accept' => 'application/json', "Content-Type" => "application/json"];
        $urlSendEmail = sprintf(
            '%s%s%s%s',
            getenv('BASE_URL_REST') . '/',
            getenv('BASE_URL_APP_REST_ENTORNO'),
            '/email/aliado/nuevocliente?',
            http_build_query([
                'idCliente' => $client->Id,
                'idAliado' => $preRegister->id_aliado,
            ])
        );

        try {
            Requests::get($urlSendEmail, $headers, ["timeout" => 120]);
        } catch (\Exception $e) {
        }

        //Crear saldos del aliado
        $alliedBalanceExists = SaldoAliados::where('aliado_id', $preRegister->id_aliado)->first();

        if (!$alliedBalanceExists) {
            $alliedBalance = new SaldoAliados();
            $alliedBalance->saldo_aliado = 0;
            $alliedBalance->saldo_disponible = 0;
            $alliedBalance->saldo_retenido = 0;
            $alliedBalance->minimo_retiro = $confAllied->minimo_retiro;
            $alliedBalance->aliado_id = $preRegister->id_aliado;
            $alliedBalance->alianza_id = $preRegister->alianza_id;
            $alliedBalance->save();
        }

        //insertamos en la tabla comisiones cliente aliado
        $commissionAlliedClient = new ComisionClienteAliado();
        $commissionAlliedClient->aliado_id = $preRegister->id_aliado;
        $commissionAlliedClient->cliente_id = $client->Id;
        $commissionAlliedClient->tipo_comision = 1;
        $commissionAlliedClient->valor_comision = $confAllied->comision;
        $commissionAlliedClient->save();
    }

    /**
     * @param Clientes $client
     * @throws \Exception
     */
    private function saveClientTariff(Clientes $client)
    {
        //validar si ya existe un cliente con el email o ducumento registrado
        $existsClient = DB::table('clientes');
        $existsClient
            ->where('Id', '<>', $client->Id)
            ->where(function ($query) use ($client) {
                $query
                    ->where('email', $client->email)
                    ->orWhere('documento', $client->documento);
            })
            ->get();

        if (0 === $existsClient->count()) {
            if ($client->id_plan !== 1011 && null !== $client->id_aliado) {
                $currentDate = new \DateTime('now');
                $partnerApplyComission = ClientesAplicarTarifa::where('cliente_id', $client->id_aliado)->first();
                if (
                    null !== $partnerApplyComission
                    && $currentDate->format('Y-m-d') <= $partnerApplyComission->fecha_limite->format('Y-m-d')
                    && true === boolval($partnerApplyComission->activo)
                ) {
                    $clientTariff = new ClientesRegistroTarifa();
                    $clientTariff->cliente_id = $client->Id;
                    $clientTariff->tarifa = 0;
                    $clientTariff->created_at = new \DateTime('now');
                    $clientTariff->fecha_fin = new \DateTime($partnerApplyComission->fecha_limite);
                    $clientTariff->tope_transaccion = 0;
                    $clientTariff->vincula_cuenta_davivienda = true;
                    $clientTariff->save();
                }
            }
        }
    }

    /**
     * @param int $clientId
     * @param $dataAccount
     * @param string $typeAccount
     */
    private function createAccountBank(int $clientId, $dataAccount, string $typeAccount)
    {
        $idAccountType = $typeAccount === "DP" ? 3 : ($typeAccount === "CA" ? 1 : 2);

        $accountBank = new CuentasBancarias();
        $accountBank->numero_tarjeta = $dataAccount["accountEncrypt"];
        $accountBank->numero_corto = $dataAccount["shortNumber"];
        $accountBank->banco_id = 1421;
        $accountBank->cliente_id = $clientId;
        $accountBank->tipo_cuenta_id = $idAccountType;
        $accountBank->estado_id = $typeAccount == "DP" || $typeAccount == "CA" ? 1 : 2;
        $accountBank->respuesta_id = $typeAccount == "DP" || $typeAccount == "CA" ? 1 : 0;
        $accountBank->fecha_apertura = new \DateTime("now");
        $accountBank->predeterminada = 1;
        $accountBank->tipo_cuenta_davivienda = $typeAccount;
        $accountBank->save();
    }

    /**
     * @param $preRegister
     * @param $arClient
     * @return bool
     * @throws \Exception
     */
    private function setGrantUSerOauth($preRegister, $arClient): bool
    {
        try {
            $dataTime = new \DateTime("now");
            $arGrantUserOauth = GrantUserOauth::where("id_oauth", $preRegister->id_social_network)->where("networks_social", $preRegister->social_network)->first();

            if (!$arGrantUserOauth) {
                $arGrantUserOauth = new GrantUserOauth();
                $arGrantUserOauth->id_oauth = ($preRegister->id_social_network);
                $arGrantUserOauth->token = ($arClient->Id);
                $arGrantUserOauth->full_name = ($preRegister->names . " " . $preRegister->surnames);
                $arGrantUserOauth->given_name = ($preRegister->names);
                $arGrantUserOauth->family_name = ($preRegister->surnames);
                $arGrantUserOauth->networks_social = ($preRegister->social_network);
                $arGrantUserOauth->created_at = ($dataTime);
            }
            $arGrantUserOauth->id_cliente = ($arClient->Id);
            $arGrantUserOauth->email = ($preRegister->email);
            $arGrantUserOauth->updated_at = ($dataTime);

            $arGrantUserOauth->save();
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param Clientes $client
     * @param PreRegister $preRegister
     * @return bool
     */
    private function saveApifyServiceClient(Clientes $client, PreRegister $preRegister): bool
    {
        try {
            $arApifyClients = new ApifyClientes();
            $arApifyClients->apify_cliente_id = $preRegister->id_cliente_entidad_aliada;
            $arApifyClients->cliente_id = $client->Id;
            $arApifyClients->fecha_creacion = new \DateTime("now");
            $arApifyClients->apify_cliente_id =
                isset($preRegister->id_cliente_entidad_aliada)
                    ? $preRegister->id_cliente_entidad_aliada
                    : 4877;
            $arApifyClients->save();
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }

    /**
     * @param PreRegister $preRegister
     * @param bool $isRegisterExpress
     */
    private function saveClientTerms(PreRegister $preRegister, $isRegisterExpress = false, $confCountry = self::COLOMBIA)
    {
        $epaycoTerms = TerminosEpayco::select('terminos_epayco.*')
        ->join('conf_pais', 'terminos_epayco.id_conf_pais', 'conf_pais.id')
        ->where('conf_pais.cod_pais', $confCountry)
        ->get();
      
        foreach ($epaycoTerms as $epaycoTerm) {
            $clientTerms = ClientesTerminos::where('id_preregistro', $preRegister->id)
                ->where('id_preregistro', $preRegister->id)
                ->where('id_termino', $epaycoTerm->id)
                ->where('version', $epaycoTerm->version)
                ->first();
            if (!$clientTerms) {
                $clientTerms = new ClientesTerminos();
            }
            $clientTerms->id_cliente = $preRegister->cliente_id;
            $clientTerms->id_termino = $epaycoTerm->id;
            $clientTerms->version = $epaycoTerm->version;
            $clientTerms->acepto = $isRegisterExpress === true ? false : true;
            $clientTerms->id_preregistro = $preRegister->id;
            $clientTerms->fecha = new \DateTime('now');
            $clientTerms->save();
        }
    }

    private function updateRestrictiveClientList(PreRegister $preRegister)
    {
        $clientRestrictiveList = ClientsRestrictiveList::where('id_pre_registro', $preRegister->id)->first();

        if ($clientRestrictiveList) {
            $clientRestrictiveList->id_cliente = $preRegister->cliente_id;
            $clientRestrictiveList->save();

            $clientTypeRisk = new ClientesTipoRiesgo();
            $clientTypeRisk->id_cliente = $preRegister->cliente_id;
            $clientTypeRisk->id_tipo_riesgo = $preRegister->restricted_user ? RiskTypeClient::NOT_OBJECTIVE : RiskTypeClient::OBJECTIVE;
            $clientTypeRisk->save();
        }
    }

    /**
     * @param int $clientId
     */
    private function saveLimEmailSms(int $clientId)
    {
        $emailSms = new LimEmailSms();
        $emailSms->cliente_id = ($clientId);
        $approved = '{"email":"si","sms":"no"}';
        $emailSms->aprobado = ($approved);
        $emailSms->save();
    }

    /**
     * @param Clientes $client
     * @param array $products
     */
    private function sameProductsEntityAllied(Clientes $client, array $products)
    {
        foreach ($products as $product) {
            $productClient = new ProductosClientes();
            $productClient->fecha_creacion = new \DateTime('now');
            $productClient->cliente_id = $client->Id;
            $productClient->estado = 5;
            $productClient->producto_id = $product;
            $productClient->save();
        }
    }

    /**
     * @param Clientes $client
     * @param $idAllied
     */
    private function samePaymentMethodsEntityAllied(Clientes $client, $idAllied)
    {
        $paymentMethodsEntityAllied = MediosPagoClientes::where('id_cliente', $idAllied)->get();
        $paymentMethodsClient = MediosPagoClientes::where('id_cliente', $client->Id)->get();

        foreach ($paymentMethodsEntityAllied as $paymentMethod) {
            $paymentMethodClient = $paymentMethodsClient->firstWhere('id_medio', $paymentMethod->id_medio);
            if (!$paymentMethodClient) {
                $paymentMethodClient = new MediosPagoClientes();
            }
            $paymentMethodClient->id_cliente = $client->Id;
            $paymentMethodClient->id_medio = $paymentMethod->id_medio;
            $paymentMethodClient->estado = $paymentMethod->estado;
            $paymentMethodClient->bancaria_id = $paymentMethod->bancaria_id;
            $paymentMethodClient->comision = $paymentMethod->comision;
            $paymentMethodClient->valor_comision = $paymentMethod->valor_comision;
            $paymentMethodClient->red = $paymentMethod->red;
            $paymentMethodClient->save();
        }
    }


    /**
     * @param $preRegister PreRegister
     * @param $email
     * @param $fieldValidation
     * @param $resultClient
     * @param $data
     */
    public function data(&$preRegister, $email, $fieldValidation, $resultClient, &$data)
    {
        $preRegister->save();

        $client = Clientes::where('id', $preRegister->cliente_id)->first();

        $this->saveAditionalInfo($fieldValidation, $client);

        $client_detail = DetalleClientes::where('id_cliente', $client->Id)->first();
        $llaves = LlavesClientes::where('cliente_id', $client->Id)->first();
        $arr_llaves = array('apiKeys' => array('publicKey' => isset($llaves->public_key) ? $llaves->public_key : "", 'privateKey' => isset($llaves->private_key_decrypt) ? $llaves->private_key_decrypt : "", "pKey" => $client->key_cli));

        $data = $this->getClientInfoBasic($client, $client_detail, $arr_llaves);
        $data["proforma"] = $resultClient["data"];
        if (isset($fieldValidation["subdomain"]) && $fieldValidation["subdomain"] != "") {
            $domainCreate = $this->createSubdomain($fieldValidation["subdomain"], null, $client->Id);
            $data["subdomainCreated"] = $domainCreate;
        }
    }

    /**
     * @param $register array
     * @param $client Clientes
     * @return bool
     */
    private function saveAditionalInfo($register, $client)
    {
        try {
            $client->promedio_ventas = isset($register["averageSell"]) ? $register["averageSell"] : null;
            $client->tipo_nacionalidad_clientes = isset($register['clientTypeNationality']) ? $register['clientTypeNationality'] : null;
            $client->fecha_expedicion = isset($register["expeditionDate"]) ? new \DateTime($register["expeditionDate"]) : null;
            $client->pagweb = isset($register["website"]) ? $register["website"] : null;

            $this->saveDateLocation($register, $client);
            if ($client->id_plan !== 1011) {
                $this->saveBusinessOwner($register, $client);

                if (isset($register["bankAccountType"]) && isset($register["bankAccountNumber"]) && $register["bankAccountType"] && $register["bankAccountNumber"]) {
                    ///Verificar numero de cuenta daviplata
                    $shortNumber = substr($register["bankAccountNumber"], -4);
                    $accountEncrypt = $this->getAccountNumber($shortNumber, $register["bankAccountType"], $register["docNumber"], $register["docType"], $register["bankAccountNumber"]);
                    if ($accountEncrypt) {
                        $dataAccount = ["shortNumber" => $shortNumber, "accountEncrypt" => $accountEncrypt];
                        $this->createAccountBank($client->Id, $dataAccount, $register["bankAccountType"]);
                    }
                }
            }
            $client->save();
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }

    private function createSubdomain($dominio, $type, $clientId)
    {

        $data = ["subdomain" => $dominio];
        $repuestaSubdominio = $this->buscarGeneral($data, true);
        if ($repuestaSubdominio) {
            $repuestaSubdominio = json_decode($repuestaSubdominio, true);
            if ($repuestaSubdominio["success"] && isset($repuestaSubdominio["result"]) && count($repuestaSubdominio["result"]) > 0) {
                return false;
            } else {
                $crearSubdominio = $this->crearGeneral(["name" => $dominio], true);
                $crearSubdominio = json_decode($crearSubdominio, true);
                if ($crearSubdominio && isset($crearSubdominio["success"]) && $crearSubdominio["success"]) {
                    $confClienteDetalle = DetalleConfClientes::where("cliente_id", $clientId)
                        ->where("config_id", 39)->first();
                    if (!$confClienteDetalle) {
                        $confClienteDetalle = new DetalleConfClientes();
                        $confClienteDetalle->cliente_id = $clientId;
                        $confClienteDetalle->config_id = 39;
                    } else {
                        $subdomain = $confClienteDetalle->valor;
                        $positionDominio = strpos($subdomain, getenv("CLOUDFLARE_DOMAIN"));
                        $subdominio = substr($subdomain, 8, $positionDominio - 9);
                        $this->eliminarGeneral(["subdomain" => $subdominio]);
                    }
                    $confClienteDetalle->valor = "https://" . $dominio . "." . getenv("CLOUDFLARE_DOMAIN");
                    $confClienteDetalle->save();
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }


    private function saveDateLocation($register, &$client)
    {
        try {
            $department = isset($register["department"]) ? $register["department"] : "";
            $city = isset($register["city"]) ? $register["city"] : "";
            $addressNomenclatureType = isset($register["addressNomenclatureType"]) ? $register["addressNomenclatureType"] : "";
            $addressPropertyType = isset($register["addressPropertyType"]) ? $register["addressPropertyType"] : "";
            $addressPropertyTypeDescription = isset($register["addressPropertyTypeDescription"]) ? $register["addressPropertyTypeDescription"] : "";
            $addressNomenclature = isset($register["addressNomenclature"]) ? $register["addressNomenclature"] : "";
            $addressNumberOne = isset($register["addressNumberOne"]) ? $register["addressNumberOne"] : "";
            $addressNumberTwo = isset($register["addressNumberTwo"]) ? $register["addressNumberTwo"] : "";
            $addressDescription = isset($register["addressDescription"]) ? $register["addressDescription"] : "";

            $address = isset($register["address"]) ? $register["address"] : "";

            if ($department != "") {
                $client->id_region = $department;
            }
            if ($city != "") {
                $client->id_ciudad = $city;
            }
            if ($address != "" && $client->direccion === "") {
                $client->direccion = $address;
            }
            if ($addressNomenclatureType != "") {
                $client->dir_tipo_nomenclatura = $addressNomenclatureType;
            }
            if ($addressPropertyType != "") {
                $client->dir_tipo_propiedad = $addressPropertyType;
            }
            if ($addressPropertyTypeDescription != "") {
                $client->dir_detalle_tipo_propiedad = $addressPropertyTypeDescription;
            }
            if ($addressNomenclature != "") {
                $client->dir_numero_nomenclatura = $addressNomenclature;
            }
            if ($addressNumberOne != "") {
                $client->dir_numero_puerta1 = $addressNumberOne;
            }
            if ($addressNumberTwo != "") {
                $client->dir_numero_puerta2 = $addressNumberTwo;
            }
            if ($addressDescription != "") {
                $client->dir_descripcion = $addressDescription;
            }
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }

    private function saveBusinessOwner($register, $client)
    {
        try {
            $arContactClients = ContactosClientes::where('id_cliente', $client->Id)->where('tipo_contacto', 'legal')->first();
            if (!$arContactClients) {
                $arContactClients = new ContactosClientes();
                $arContactClients->id_cliente = $client->Id;
                $arContactClients->tipo_contacto = 'legal';
            }
            $arContactClients->documento = $client->documento;
            $arContactClients->tipo_doc = $client->tipo_doc;
            $arContactClients->celular = $register["mobilePhone"];
            $arContactClients->nombre = $register["firstNames"];
            $arContactClients->apellido = $register["lastNames"];
            $arContactClients->profesion = isset($register["profession"]) ? $register["profession"] : "";
            $arContactClients->ind_pais = isset($register["prefijo"]) ? $register["prefijo"] : "";
            $arContactClients->email = $register["mail"];

            $arContactClients->save();


        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }
}
