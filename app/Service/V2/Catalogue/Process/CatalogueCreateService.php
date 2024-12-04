<?php

namespace App\Service\V2\Catalogue\Process;

use App\Exceptions\GeneralException;
use App\Helpers\Messages\CommonText;
use App\Helpers\ClientS3;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\ValidateUrlImage;
use App\Http\Validation\Validate as Validate;
use App\Models\SplitPaymentsClientsAppsMerchants;
use App\Models\SplitPaymentsReceivers;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use App\Repositories\V2\CloudflareRepository;
use App\Repositories\V2\ProductRepository;
use Illuminate\Support\Facades\Log;

class CatalogueCreateService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;
    protected ProductRepository $productRepository;
    protected CloudflareRepository $cloudflareRepository;
    protected ClientRepository $clientRepository;

    public function __construct(CatalogueRepository $catalogueRepository, ClientRepository $clientRepository, ProductRepository $productRepository, CloudflareRepository $cloudflareRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
        $this->productRepository = $productRepository;
        $this->cloudflareRepository = $cloudflareRepository;
        $this->clientRepository = $clientRepository;
    }
    public function process($data)
    {
        try {

            $fieldValidation = $data;
            $clientId = $fieldValidation[CommonText::CLIENTID];

            $name = trim($fieldValidation["name"]);
            $id = $this->getFieldValidation($fieldValidation, "id");
            $img = $this->getFieldValidation($fieldValidation, "image");
            $update = false;
            $companyName = $this->getFieldValidation($fieldValidation, "companyName");
            $contactPhone = $this->getFieldValidation($fieldValidation, "contactPhone");
            $contactEmail = $this->getFieldValidation($fieldValidation, "contactEmail");

            $ownDomain = $this->getFieldValidation($fieldValidation, "ownDomain");
            $deleteOwnDomainValue = $this->getFieldValidation($fieldValidation, "deleteOwnDomainValue");
            $deleteOwnSubDomainValue = $this->getFieldValidation($fieldValidation, "deleteOwnSubDomainValue");
            $ownDomainValue = $this->getFieldValidation($fieldValidation, "ownDomainValue");
            $ownSubDomainValue = $this->getFieldValidation($fieldValidation, "ownSubDomainValue");
            $cname = $this->getFieldValidation($fieldValidation, "cname");

            $whatsappActive = $this->getFieldValidation($fieldValidation, "whatsappActive", false);
            $color = $this->getFieldValidation($fieldValidation, CommonText::COLOR, "#999999");
            $banners = $this->getFieldValidation($fieldValidation, CommonText::BANNERS, ["", "", ""]);
            $origin = $this->getFieldValidation($fieldValidation, "origin");
            $progress = $this->getFieldValidation($fieldValidation, "progress");
            $analytics = $this->getFieldValidation($fieldValidation, "analytics");
            $active = $this->getFieldValidation($fieldValidation, CommonText::ACTIVE_ENG);
            $currency = $this->validateCurrencyCode($fieldValidation, $origin);
            $default_language = $this->getFieldValidation($fieldValidation, "default_language");

            $indicativoPais = $this->formatCountryCode($fieldValidation);
            $apifyClient = $this->getAlliedEntity($clientId);
            $providerDelivery = $this->getFieldValidation($fieldValidation, "providerDelivery");
            $epaycoDeliveryProvider = $this->getFieldValidation($fieldValidation, "epaycoDeliveryProvider");
            $senderType = $this->getFieldValidation($fieldValidation, "senderType");
            $senderFirstname = $this->getFieldValidation($fieldValidation, "senderFirstname");
            $senderLastname = $this->getFieldValidation($fieldValidation, "senderLastname");
            $senderDocType = $this->getFieldValidation($fieldValidation, "senderDocType");
            $senderDoc = $this->getFieldValidation($fieldValidation, "senderDoc");
            $senderPhone = $this->getFieldValidation($fieldValidation, "senderPhone");
            $senderBusiness = $this->getFieldValidation($fieldValidation, "senderBusiness");
            $epaycoDeliveryProviderValues = $this->getFieldValidation($fieldValidation, "epaycoDeliveryProviderValues");
            $pickupCity = $this->getFieldValidation($fieldValidation, "pickupCity");
            $pickupDepartament = $this->getFieldValidation($fieldValidation, "pickupDepartament");
            $pickupAddress = $this->getFieldValidation($fieldValidation, "pickupAddress");
            $pickupConfigurationId = $this->getFieldValidation($fieldValidation, "pickupConfigurationId");
            $automaticPickup = $this->getFieldValidation($fieldValidation, "automaticPickup");
            $freeDelivery = $this->getFieldValidation($fieldValidation, "freeDelivery");
            $finish = $this->getFieldValidation($fieldValidation, "finish", false);

            // $this->handleSplitPayments($epaycoDeliveryProviderValues,$clientId);

            $deliveryProviderProperties = [
                CommonText::PROVIDER_DELIVERY => $providerDelivery,
                CommonText::EPAYCO_LOGISTIC => $epaycoDeliveryProvider,
                CommonText::SENDER_TYPE => $senderType,
                CommonText::SENDER_FIRSTNAME => $senderFirstname,
                CommonText::SENDER_LASTNAME => $senderLastname,
                CommonText::SENDER_DOC_TYPE => $senderDocType,
                CommonText::SENDER_DOC => $senderDoc,
                CommonText::SENDER_PHONE => $senderPhone,
                CommonText::SENDER_BUSINESS => $senderBusiness,
                CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES => $epaycoDeliveryProviderValues,
                CommonText::PICKUP_CITY => $pickupCity,
                CommonText::PICKUP_DEPARTAMENT => $pickupDepartament,
                CommonText::PICKUP_ADDRESS => $pickupAddress,
                CommonText::PICKUP_CONFIGURATION_ID => $pickupConfigurationId,
                CommonText::AUTOMATIC_PICKUP => $automaticPickup,
                CommonText::FREE_DELIVERY => $freeDelivery,
            ];

            $catalogueData = null;
            $catalogue = null;
            /** @var $catalogue Catalogo */
            if ($this->formatId($id) > 0) {
                $update = true;
                $result = $this->catalogueRepository->findByIdAndClientIdNoEstatus($id, $clientId);
                // consultar los datos del catalogo a elasticsearch

                $catalogueResult = $result->toArray();

                if ($catalogueResult && count($catalogueResult) > 0) {
                    $catalogueData = (object) $catalogueResult[0];

                    $this->validateCatalogueExistForUpdate($name, $catalogueData->nombre, $clientId);

                    $catalogue = [
                        "fecha" => $catalogueData->fecha,
                        "fecha_actualizacion" => date("c"),
                        "estado" => $catalogueData->estado,
                        "imagen" => $catalogueData->imagen,
                        "nombre" => $catalogueData->nombre,
                        CommonText::CLIENT_ID => $catalogueData->cliente_id,
                        "id" => $catalogueData->id,
                        CommonText::BANNERS => $this->getFieldValidation((array) $catalogueData, CommonText::BANNERS, ["", "", ""]),
                    ];

                    $catalogue["imagen"] = $this->saveImageInAWS($img, $clientId, $name, $catalogue);

                    $this->uploadBanners($catalogue, $name, $banners, $origin);

                    $this->createEpaycoProperties($catalogue, $companyName, $contactPhone, $contactEmail, $whatsappActive, $origin, $color, $indicativoPais, $progress, $active, $catalogueData, $ownDomain, $ownDomainValue, $ownSubDomainValue, $deleteOwnDomainValue, $deleteOwnSubDomainValue);

                    $this->addEpaycoProperties($catalogue, $origin, $currency, $default_language, $analytics, $deliveryProviderProperties);
                } else {
                    throw new GeneralException("Catalogue not found", [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => 'Catalogue not found']]);
                }
            } else {

                $result = $this->catalogueRepository->findByNameAndClientIdAndStatus($name, $clientId, true);
                $catalogueExistResult = $result->toArray();
                $this->validateCatalogueExist($catalogueExistResult);

                $timeArray = explode(" ", microtime());
                $timeArray[0] = str_replace('.', '', $timeArray[0]);

                $createDate = date("c");

                $catalogue = [
                    "id" => (int) ($timeArray[1] . substr($timeArray[0], 2, 3)),
                    "fecha" => $createDate,
                    "fecha_actualizacion" => $createDate,
                    CommonText::CLIENT_ID => $clientId,
                    CommonText::ENTIDAD_ALIADA => $apifyClient["alliedEntityId"],
                    CommonText::FECHA_CREACION_CLIENTE => $apifyClient["clientCreatedAt"],
                    "estado" => true,
                    "imagen" => "",
                    "categorias" => [
                        [
                            "fecha" => date("c"),
                            "estado" => true,
                            "id" => 1,
                            "nombre" => "General",
                            CommonText::CLIENT_ID => $clientId,
                            "img" => "",
                            "catalogo_id" => (int) ($timeArray[1] . substr($timeArray[0], 2, 3)),
                        ],

                    ],
                    CommonText::BANNERS => ["", "", ""],
                ];
                $catalogue["imagen"] = $this->saveImageInAWS($img, $clientId, $name, $catalogue);

                $this->uploadBanners($catalogue, $name, $banners, $origin);

                $this->createEpaycoProperties($catalogue, $companyName, $contactPhone, $contactEmail, $whatsappActive, $origin, $color, $indicativoPais, '', true, [], $ownDomain, $ownDomainValue, $ownSubDomainValue, $deleteOwnDomainValue, $deleteOwnSubDomainValue);
                $this->addEpaycoProperties($catalogue, $origin, $currency, $default_language, $analytics, $deliveryProviderProperties);
            }

            $catalogue["nombre"] = $name;

            if (!$update) {
                $verb = "created";

                $resultData = $this->catalogueRepository->create($catalogue);
                if ($resultData) {
                    $anukisSuccess = true;
                } else {
                    $anukisSuccess = false;
                }

                $returnDate = $catalogue["fecha"];
                if ($ownDomain && $cname && $cname !== '') {
                    $this->verifyAndRegisterCname($clientId, $cname);
                }
            } else {
                $verb = "updated";
          

              
                $dataForUpdate = [
                        'nombre' => $catalogue["nombre"],
                        'imagen' => $catalogue["imagen"],
                        'fecha_actualizacion' => $catalogue["fecha_actualizacion"],
                        'nombre_empresa' => $catalogue[CommonText::COMPANY_NAME],
                        'procede' => $catalogue[CommonText::PROCEED],
                        'telefono_contacto' => $catalogue[CommonText::CONTACT_PHONE_ES],
                        'correo_contacto' => $catalogue[CommonText::CONTACT_EMAIL],
                        'whatsapp_activo' => $catalogue[CommonText::WHATSAPP_ACTIVE],
                        'progreso' => $catalogue[CommonText::PROGRESS],
                        'activo' => $catalogue[CommonText::ACTIVE],
                ];

                if(!$finish){
                    $dataForUpdate["color"] = $catalogue[CommonText::COLOR];
                }

                if ($ownDomainValue != $catalogueResult[0][CommonText::OWNDOMAINVALUE] || $ownSubDomainValue != $catalogueResult[0][CommonText::OWNSUBDOMAINVALUE]) {
                    $dataForUpdate["posee_certificado"] = false;
                    $dataForUpdate["fecha_creacion_certificado"] = null;
                    $dataForUpdate["proximo_inteto"] = null;
                    $dataForUpdate["intentos_certificacion"] = null;

                }

                if (!$finish) {
                    $this->deleteOrRegisterCname($catalogue, $clientId, $cname);
                    $otherData = [
                        'banners' => $catalogue[CommonText::BANNERS],
                        'indicativo_pais' => $catalogue[CommonText::COUNTRY_CODE],
                        'moneda' => $catalogue[CommonText::CURRENCY],
                        'idioma' => $catalogue[CommonText::DEFAULT_LANGUAGE],
                        'analiticas' => $catalogue[CommonText::ANALYTICS],
                        'proveedor_envios' => $catalogue[CommonText::PROVIDER_DELIVERY],
                        'epayco_logistica' => $catalogue[CommonText::EPAYCO_LOGISTIC],
                        'tipo_remitente' => $catalogue[CommonText::SENDER_TYPE],
                        'nombre_remitente' => $catalogue[CommonText::SENDER_FIRSTNAME],
                        'apellido_remitente' => $catalogue[CommonText::SENDER_LASTNAME],
                        'tipo_documento_remitente' => $catalogue[CommonText::SENDER_DOC_TYPE],
                        'documento_remitente' => $catalogue[CommonText::SENDER_DOC],
                        'telefono_remitente' => $catalogue[CommonText::SENDER_PHONE],
                        'razon_social_remitente' => $catalogue[CommonText::SENDER_BUSINESS],
                        'lista_proveedores' => $catalogue[CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES],
                        'ciudad_recogida' => $catalogue[CommonText::PICKUP_CITY],
                        'departamento_recogida' => $catalogue[CommonText::PICKUP_DEPARTAMENT],
                        'direccion_recogida' => $catalogue[CommonText::PICKUP_ADDRESS],
                        'configuracion_recogida_id' => $catalogue[CommonText::PICKUP_CONFIGURATION_ID],
                        'recogida_automatica' => $catalogue[CommonText::AUTOMATIC_PICKUP],
                        'envio_gratis' => $catalogue[CommonText::FREE_DELIVERY],
                        'dominio_propio' => $catalogue[CommonText::OWNDOMAIN],
                        'valor_dominio_propio' => $catalogue[CommonText::OWNDOMAINVALUE],
                        'valor_subdominio_propio' => $catalogue[CommonText::OWNSUBDOMAINVALUE],
                        'eliminado_valor_dominio_propio' => $catalogue[CommonText::DELETE_OWNDOMAINVALUE],
                        'eliminado_valor_subdominio_propio' => $catalogue[CommonText::DELETE_OWNSUBDOMAINVALUE],
                    ];
                    $dataForUpdate = array_merge($dataForUpdate, $otherData);
                }

                $result = $this->catalogueRepository->updateWithClientId($id, $clientId, $dataForUpdate);
                if ($result > 0) {
                    $resultFinish = true;
                } else {
                    $resultFinish = false;
                }
                $anukisSuccess = $resultFinish;
                $returnDate = $catalogue["fecha_actualizacion"];
            }

            if ($anukisSuccess) {

                $newData = [
                    "id" => $catalogue["id"],
                    "name" => $catalogue["nombre"],
                    "image" => $catalogue["imagen"],
                    CommonText::CLIENTID => $catalogue[CommonText::CLIENT_ID],
                    "date" => date("Y-m-d H:i:s", strtotime($returnDate)),
                    "edataStatus" => "Permitido",

                ];

                $this->addEpaycoParamsToResponseData($newData, $catalogue, $origin);

                $success = true;
                $title_response = "Successful {$verb} catalogue";
                $text_response = "successful {$verb} catalogue";
                $last_action = "catalogue_{$verb}";
                $data = $newData;

                $this->deleteCatalogueRedis($catalogue);

            } else {
                $success = false;
                $title_response = "Error";
                $text_response = "Error {$verb} catalogue";
                $last_action = "{$verb} data in elasticsearch";
                $data = [];
            }
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error create new catalogue";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);

            Log::info($exception);

        } catch (GeneralException $generalException) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "generalException";
            $arr_respuesta['data'] = $generalException->getData();

            return $arr_respuesta;

            Log::info($exception);

        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function deleteCatalogueRedis($catalogue)
    {
        $redis = app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_' . $catalogue["id"]);
        if ($exist) {
            $redis->del('vende_catalogue_' . $catalogue["id"]);
        }
    }

    private function handleSplitPayments($epaycoDeliveryProviderValues, $clientId)
    {
        if (isset($epaycoDeliveryProviderValues[0]) && $epaycoDeliveryProviderValues[0] == '472') {
            $existSplitPayment = SplitPaymentsClientsAppsMerchants::where('clienteapp_id', $clientId)->where("merchant_receiver_id", $clientId)->first();
            if (!$existSplitPayment) {
                $this->saveSplitpaymentsReceiver($clientId, env('CLIENT_472_ID'));
                $this->saveSplitpaymentsReceiver($clientId, $clientId);
                $this->saveSplitPaymentsMerchants($clientId, $clientId);
            }
        }
    }

    private function saveSplitPaymentsMerchants($clientId, $merchantId)
    {
        $splitPaymentsClientsAppSMerchant = new SplitPaymentsClientsAppsMerchants();
        $splitPaymentsClientsAppSMerchant->clienteapp_id = $clientId;
        $splitPaymentsClientsAppSMerchant->fecha = date("c");
        $splitPaymentsClientsAppSMerchant->estado = 1;
        $splitPaymentsClientsAppSMerchant->tipo_comision = 1;
        $splitPaymentsClientsAppSMerchant->valor_comision = 1;
        $splitPaymentsClientsAppSMerchant->merchant_receiver_id = $merchantId;
        $splitPaymentsClientsAppSMerchant->save();
        return $splitPaymentsClientsAppSMerchant;
    }
    private function saveSplitpaymentsReceiver($clientId, $receiverId)
    {
        $splitPaymentReceiver = new SplitPaymentsReceivers();
        $splitPaymentReceiver->fecha = date("c");
        $splitPaymentReceiver->clienteapp_id = $clientId;
        $splitPaymentReceiver->merchant_receiver_id = $receiverId;
        $splitPaymentReceiver->receiver_id = $receiverId;
        $splitPaymentReceiver->estado = 1;
        $splitPaymentReceiver->tipo_comision = 1;
        $splitPaymentReceiver->valor_comision = 1;
        $splitPaymentReceiver->save();
        return $splitPaymentReceiver;
    }

    private function validateCatalogueExistForUpdate($newName, $oldName, $clientId)
    {
        if (getenv(CommonText::SOCIAL_SELLER_DUPLICATE_VALIDATION) && getenv(CommonText::SOCIAL_SELLER_DUPLICATE_VALIDATION) == CommonText::ACTIVE_ENG) {
            if ($oldName != $newName) {

                $catalogueExistResult = $this->catalogueRepository->findByNameAndClientId($newName, $clientId);
                $result = $catalogueExistResult->toArray();

                if (!empty($result)) {
                    throw new GeneralException(CommonText::CATALOGUE_NAME_EXIST, [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => CommonText::CATALOGUE_NAME_EXIST]]);
                }
            }
        }
    }

    private function getFieldValidation($fields, $name, $default = "")
    {

        return isset($fields[$name]) ? $fields[$name] : $default;
    }

    private function formatId($id)
    {

        return $id == "" ? 0 : $id;
    }

    private function validateCatalogueExist($consultCatalogueExistResult)
    {
        if (getenv(CommonText::SOCIAL_SELLER_DUPLICATE_VALIDATION) && getenv(CommonText::SOCIAL_SELLER_DUPLICATE_VALIDATION) == CommonText::ACTIVE_ENG) {
            if (!empty($consultCatalogueExistResult["data"])) {
                throw new GeneralException(CommonText::CATALOGUE_NAME_EXIST, [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => CommonText::CATALOGUE_NAME_EXIST]]);
            }
        }
    }

    private function validateCompanyName($companyName)
    {

        if ($companyName == "") {
            throw new GeneralException("Debe indicar el nombre de la empresa", [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => 'Debe indicar el nombre de la empresa']]);
        }
    }

    private function getCatalogueProgress($catalogue)
    {

        $progress = "completado";

        if ((!isset($catalogue[CommonText::COMPANY_NAME]) || $catalogue[CommonText::COMPANY_NAME] == "") ||
            (!isset($catalogue["imagen"]) || $catalogue["imagen"] == "") ||
            (!isset($catalogue[CommonText::CONTACT_PHONE_ES]) || $catalogue[CommonText::CONTACT_PHONE_ES] == "") ||
            (!isset($catalogue[CommonText::CONTACT_EMAIL]) || $catalogue[CommonText::CONTACT_EMAIL] == "") ||
            (!isset($catalogue[CommonText::COLOR]) || $catalogue[CommonText::COLOR] == "") ||
            (!isset($catalogue[CommonText::BANNERS]) || ($catalogue[CommonText::BANNERS][0] == "" &&
                $catalogue[CommonText::BANNERS][1] == "" &&
                $catalogue[CommonText::BANNERS][2] == ""
            ))
        ) {
            $progress = "procesando";
        }

        return $progress;
    }

    private function saveImageInAWS($img, $clientId, $name, $catalogue)
    {

        $imageRoute = "";

        if ($img) {
            if ($img != "delete" && substr($img, 0, 5) != 'https') {
                $data = explode(',', $img);
                $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
                $sacarExt = explode('image/', $data[0]);
                $sacarExt = explode(';', $sacarExt[1]);

                if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
                    throw new GeneralException("file format not allowed", [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => 'file format not allowed']]);
                }
                $base64 = base64_decode($data[1]);
                file_put_contents(
                    $tmpfname . "." . $sacarExt[0],
                    $base64
                );

                $fechaActual = new \DateTime('now');

                //Subir los archivos
                $token = random_int(100, 999);
                $nameFile = "{$clientId}_{$name}_{$fechaActual->getTimestamp()}_{$token}.{$sacarExt[0]}";
                $imageRoute = "vende/catalogo/{$nameFile}";
                $bucketName = getenv("AWS_BUCKET_MULTIMEDIA_EPAYCO");

                $clientS3 = new ClientS3();
                $clientS3->uploadFileAws($bucketName, $tmpfname . "." . $sacarExt[0], $imageRoute);

                unlink($tmpfname . "." . $sacarExt[0]);
            } else if (substr($img, 0, 5) == 'https') {
                $imageRoute = substr($img, strlen(ValidateUrlImage::getDomainFromUrl($img)) + 1);
            }
        } else if (isset($catalogue["imagen"]) && $catalogue["imagen"] != "") {
            $imageRoute = $catalogue["imagen"];
        }

        return $imageRoute;
    }

    private function createEpaycoProperties(&$catalogue, $companyName, $contactPhone, $contactEmail, $whatsappActive, $origin, $color, $indicativoPais, $progress = '', $active = true, $catalogueData = null, $ownDomain = false, $ownDomainValue = "", $ownSubDomainValue = "", $deleteOwnDomainValue = true, $deleteOwnSubDomainValue = true)
    {

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $this->validateCompanyName($companyName);

            $this->validateDataToUpdate($catalogueData, $contactEmail, $contactPhone, $color, $indicativoPais, $whatsappActive);

            if (empty($contactPhone) && isset($catalogueData->telefono_contacto)) {
                $contactPhone = $catalogueData->telefono_contacto;
            }
            if (empty($contactEmail) && isset($catalogueData->correo_contacto)) {
                $contactEmail = $catalogueData->correo_contacto;
            }

            $catalogue[CommonText::COMPANY_NAME] = $companyName;
            $catalogue[CommonText::OWNDOMAIN] = $ownDomain;
            $catalogue[CommonText::OWNDOMAINVALUE] = isset($catalogueData->valor_dominio_propio) ? ($ownDomainValue == "" ? $catalogueData->valor_dominio_propio : $ownDomainValue) : $ownDomainValue;
            $catalogue[CommonText::OWNSUBDOMAINVALUE] = isset($catalogueData->valor_subdominio_propio) ? ($ownSubDomainValue == "" ? $catalogueData->valor_subdominio_propio : $ownSubDomainValue) : $ownSubDomainValue;
            $catalogue[CommonText::DELETE_OWNDOMAINVALUE] = $deleteOwnDomainValue;
            $catalogue[CommonText::DELETE_OWNSUBDOMAINVALUE] = $deleteOwnSubDomainValue;
            $catalogue[CommonText::PROCEED] = $origin;
            $catalogue[CommonText::CONTACT_PHONE_ES] = $contactPhone;
            $catalogue[CommonText::CONTACT_EMAIL] = $contactEmail;
            $catalogue[CommonText::WHATSAPP_ACTIVE] = $whatsappActive;
            $catalogue[CommonText::COLOR] = $color;
            $catalogue[CommonText::PROGRESS] = trim($progress) == 'publicado' ? trim($progress) : $this->getCatalogueProgress($catalogue);
            $catalogue[CommonText::ACTIVE] = $active;
            $catalogue[CommonText::COUNTRY_CODE] = $indicativoPais;
        }
    }

    private function addEpaycoProperties(&$catalogue, $origin, $currency, $default_language, $analitycs, $deliveryProviderProperties)
    {
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $catalogue[CommonText::CURRENCY] = $currency;
            $catalogue[CommonText::DEFAULT_LANGUAGE] = $default_language;
            $catalogue[CommonText::ANALYTICS] = $this->getAnalyticsElasticParams($analitycs);
            $catalogue[CommonText::PROVIDER_DELIVERY] = $deliveryProviderProperties[CommonText::PROVIDER_DELIVERY];
            $catalogue[CommonText::EPAYCO_LOGISTIC] = $deliveryProviderProperties[CommonText::EPAYCO_LOGISTIC];
            $catalogue[CommonText::SENDER_TYPE] = $deliveryProviderProperties[CommonText::SENDER_TYPE];
            $catalogue[CommonText::SENDER_FIRSTNAME] = $deliveryProviderProperties[CommonText::SENDER_FIRSTNAME];
            $catalogue[CommonText::SENDER_LASTNAME] = $deliveryProviderProperties[CommonText::SENDER_LASTNAME];
            $catalogue[CommonText::SENDER_DOC_TYPE] = $deliveryProviderProperties[CommonText::SENDER_DOC_TYPE];
            $catalogue[CommonText::SENDER_DOC] = $deliveryProviderProperties[CommonText::SENDER_DOC];
            $catalogue[CommonText::SENDER_PHONE] = $deliveryProviderProperties[CommonText::SENDER_PHONE];
            $catalogue[CommonText::SENDER_BUSINESS] = $deliveryProviderProperties[CommonText::SENDER_BUSINESS];
            $catalogue[CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES] = $deliveryProviderProperties[CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES];
            $catalogue[CommonText::PICKUP_CITY] = $deliveryProviderProperties[CommonText::PICKUP_CITY];
            $catalogue[CommonText::PICKUP_DEPARTAMENT] = $deliveryProviderProperties[CommonText::PICKUP_DEPARTAMENT];
            $catalogue[CommonText::PICKUP_ADDRESS] = $deliveryProviderProperties[CommonText::PICKUP_ADDRESS];
            $catalogue[CommonText::PICKUP_CONFIGURATION_ID] = $deliveryProviderProperties[CommonText::PICKUP_CONFIGURATION_ID];
            $catalogue[CommonText::AUTOMATIC_PICKUP] = $deliveryProviderProperties[CommonText::AUTOMATIC_PICKUP];
            $catalogue[CommonText::FREE_DELIVERY] = $deliveryProviderProperties[CommonText::FREE_DELIVERY];
        }
    }

    private function validateDataToUpdate($catalogueData, $contactEmail, $contactPhone, &$color, &$indicativoPais, &$whatsappActive)
    {
        if (!empty($catalogueData)) {
            if (empty($contactPhone) && empty($contactEmail) && isset($catalogueData->whatsapp_activo)) {
                $whatsappActive = $catalogueData->whatsapp_activo;
            }

            if ($color == "#999999" && isset($catalogueData->color)) {
                $color = $catalogueData->color;
            }

            if ($indicativoPais == "" && isset($catalogueData->indicativo_pais)) {
                $indicativoPais = $catalogueData->indicativo_pais;
            }
        }
    }

    private function addEpaycoParamsToResponseData(&$newData, $catalogue, $origin)
    {
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $newData["companyName"] = $catalogue[CommonText::COMPANY_NAME];

            $newData["ownDomain"] = $catalogue[CommonText::OWNDOMAIN];
            $newData["ownDomainValue"] = $catalogue[CommonText::OWNDOMAINVALUE];
            $newData["ownSubDomainValue"] = $catalogue[CommonText::OWNSUBDOMAINVALUE];
            //Si deleteOwnDomainValue es true el dominio se encuentra inactivo
            $newData["deleteOwnDomainValue"] = $catalogue[CommonText::DELETE_OWNDOMAINVALUE];
            //Si deleteOwnSubDomainValue es true el subDominio se encuentra inactivo
            $newData["deleteOwnSubDomainValue"] = $catalogue[CommonText::DELETE_OWNSUBDOMAINVALUE];

            $newData["origin"] = $catalogue[CommonText::PROCEED];
            $newData["contactPhone"] = $catalogue[CommonText::CONTACT_PHONE_ES];
            $newData["contactEmail"] = $catalogue[CommonText::CONTACT_EMAIL];
            $newData["whatsappActive"] = $catalogue[CommonText::WHATSAPP_ACTIVE];
            $newData[CommonText::COLOR] = $catalogue[CommonText::COLOR];
            $newData["progress"] = $catalogue[CommonText::PROGRESS];
            $newData[CommonText::BANNERS] = $catalogue[CommonText::BANNERS];
            $newData[CommonText::ACTIVE_ENG] = $catalogue[CommonText::ACTIVE];
            $newData["currency"] = $catalogue[CommonText::CURRENCY];
            $newData["default_language"] = $catalogue[CommonText::DEFAULT_LANGUAGE];

            $newData[CommonText::ANALYTICS_ENG] = $this->getAnalyticsResponseParams($catalogue[CommonText::ANALYTICS]);
            $newData["providerDelivery"] = $catalogue[CommonText::PROVIDER_DELIVERY];
            $newData["epaycoDeliveryProvider"] = $catalogue[CommonText::EPAYCO_LOGISTIC];
            $newData["senderType"] = $catalogue[CommonText::SENDER_TYPE];
            $newData["senderFirstname"] = $catalogue[CommonText::SENDER_FIRSTNAME];
            $newData["senderLastname"] = $catalogue[CommonText::SENDER_LASTNAME];
            $newData["senderDocType"] = $catalogue[CommonText::SENDER_DOC_TYPE];
            $newData["senderDoc"] = $catalogue[CommonText::SENDER_DOC];
            $newData["senderPhone"] = $catalogue[CommonText::SENDER_PHONE];
            $newData["senderBusiness"] = $catalogue[CommonText::SENDER_BUSINESS];
            $newData["epaycoDeliveryProviderValues"] = $catalogue[CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES];
            $newData["pickupCity"] = $catalogue[CommonText::PICKUP_CITY];
            $newData["pickupDepartament"] = $catalogue[CommonText::PICKUP_DEPARTAMENT];
            $newData["pickupAddress"] = $catalogue[CommonText::PICKUP_ADDRESS];
            $newData["pickupConfigurationId"] = $catalogue[CommonText::PICKUP_CONFIGURATION_ID];
            $newData["automaticPickup"] = $catalogue[CommonText::AUTOMATIC_PICKUP];
            $newData["freeDelivery"] = $catalogue[CommonText::FREE_DELIVERY];
        }
    }

    private function uploadBanners(&$catalogue, $catalogueName, $banners, $origin)
    {

        if ($origin == CommonText::ORIGIN_EPAYCO && !empty($banners)) {

            for ($i = 0; $i < 3; $i++) {
                if (isset($banners[$i]) && $banners[$i] != "") {
                    if ($banners[$i] == "delete") {
                        $catalogue[CommonText::BANNERS][$i] = "";
                    } else {
                        $catalogue[CommonText::BANNERS][$i] = $this->saveImageInAWS($banners[$i], $catalogue[CommonText::CLIENT_ID], $catalogueName, $catalogue);
                    }
                }
            }
        }
    }

    private function formatCountryCode($fieldValidation)
    {
        $countryCode = $this->getFieldValidation($fieldValidation, "indicativoPais");
        return str_replace("+", "", $countryCode);
    }

    private function validateCurrencyCode($fieldValidation, $origin)
    {
        $currency = $this->getFieldValidation($fieldValidation, "currency", CommonText::COP_CURRENCY_CODE);

        if ($origin == CommonText::ORIGIN_EPAYCO && !in_array($currency, CommonText::STRING_CURRENCY_CODES)) {
            throw new GeneralException("Invalid currency code", [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => 'Invalid currency coda']]);
        }

        return $currency;
    }
    private function getAnalyticsElasticParams($analytics)
    {

        return [
            "facebook_pixel_active" => $this->getFieldValidation((array) $analytics, "facebookPixelActive", false),
            "facebook_pixel_id" => $this->getFieldValidation((array) $analytics, "facebookPixelId", ""),
            "google_analytics_active" => $this->getFieldValidation((array) $analytics, "googleAnalyticsActive", false),
            "google_analytics_id" => $this->getFieldValidation((array) $analytics, "googleAnalyticsId", ""),
            "google_tag_manager_active" => $this->getFieldValidation((array) $analytics, "googleTagManagerActive", false),
            "google_tag_manager_id" => $this->getFieldValidation((array) $analytics, "googleTagManagerId", ""),
        ];
    }

    private function getAnalyticsResponseParams($analytics)
    {

        return [
            "facebookPixelActive" => $this->getFieldValidation((array) $analytics, "facebook_pixel_active", false),
            "facebookPixelId" => $this->getFieldValidation((array) $analytics, "facebook_pixel_id", ""),
            "googleAnalyticsActive" => $this->getFieldValidation((array) $analytics, "google_analytics_active", false),
            "googleAnalyticsId" => $this->getFieldValidation((array) $analytics, "google_analytics_id", ""),
            "googleTagManagerActive" => $this->getFieldValidation((array) $analytics, "google_tag_manager_active", false),
            "googleTagManagerId" => $this->getFieldValidation((array) $analytics, "google_tag_manager_id", ""),
        ];
    }

    private function verifyAndRegisterCname($clientId, $url)
    {
        $subdominio = $this->getSubdomain($url);
        $dataUser = null;
        if ($subdominio) {
            $result = (object) $this->cloudflareRepository->consultationSubdomain($subdominio);
            if ($result->success) {
                $dataUser = $this->clientRepository->updateCname($clientId, $url);
            }
        }
        return $dataUser;
    }

    private function getSubdomain($url)
    {
        $urlParse = parse_url($this->addHttps($url));
        if (isset($urlParse['host'])) {
            $partHost = explode('.', $urlParse['host']);
            if (count($partHost) >= 2) {
                $subdomain = $partHost[0];
                return $subdomain;
            }
        }
        return null;
    }

    private function addHttps($url)
    {
        if (strpos($url, "https://") !== 0) {
            $url = "https://" . $url;
        }
        return $url;
    }

    private function deleteOrRegisterCname($newCatalogue, $clientId, $url)
    {
        $domainActive = !$newCatalogue[CommonText::DELETE_OWNDOMAINVALUE];
        $subDomainActive = !$newCatalogue[CommonText::DELETE_OWNSUBDOMAINVALUE];
        $owndomain = $newCatalogue[CommonText::OWNDOMAIN];
        $dataUser = $this->clientRepository->find($clientId);
        $subdominio = $this->getSubdomain($url);
        if ($subdominio && $owndomain && ($domainActive || $subDomainActive) && $dataUser->cname !== $url) {
            //si tengo activo dominio o subdominio propio y no tengo el cname registrado
            // registrar el cname en cloudflare y bblcliente
            $result = (object) $this->cloudflareRepository->registerSubdomain($subdominio);
            // if ($result->success) {
            $dataUser = $this->clientRepository->updateCname($clientId, $url);
            // }
        } else if (!$owndomain && !$domainActive && !$subDomainActive && ($dataUser->cname !== null || $dataUser->cname !== '')) {
            //si tengo desactivado dominio y subdominio propio y tiene un cname registrado
            // elimino el cname del registro bblcliente y de cloudflare
            $subdominio = $this->getSubdomain($dataUser->cname);
            $result = (object) $this->cloudflareRepository->consultationSubdomain($subdominio);
            if ($result->success) {
                $this->cloudflareRepository->deleteSubdomain($result->result['id']);
            }
            $dataUser = $this->clientRepository->updateCname($clientId, "");
        }
    }

}
