<?php

namespace App\Listeners\Catalogue\Process;


use App\Events\Catalogue\Process\CatalogueNewEvent;
use App\Helpers\Edata\HelperEdata;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Exceptions\GeneralException;
use App\Models\SplitPaymentsReceivers;
use App\Models\SplitPaymentsClientsAppsMerchants;
use Illuminate\Http\Request;

use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;

class CatalogueNewListener extends HelperPago
{

    /**
     * CatalogueNewListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param CatalogueNewEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(CatalogueNewEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation[CommonText::CLIENTID];
            
            $name = trim($fieldValidation["name"]);
            $id = $this->getFieldValidation($fieldValidation, "id");
            $img = $this->getFieldValidation($fieldValidation, "image");
            $update = false;
            $id_edata = $this->getFieldValidation($fieldValidation, "id_edata", null);
            $edata_estado = $this->getFieldValidation($fieldValidation, HelperEdata::EDATA_STATE, HelperEdata::STATUS_ALLOW);
            $edata_mensaje = $this->getFieldValidation($fieldValidation, "edata_mensaje");
            $companyName = $this->getFieldValidation($fieldValidation, "companyName");
            $contactPhone = $this->getFieldValidation($fieldValidation, "contactPhone");
            $contactEmail = $this->getFieldValidation($fieldValidation, "contactEmail");

            $ownDomain = $this->getFieldValidation($fieldValidation, "ownDomain");
            $ownDomainValue = $this->getFieldValidation($fieldValidation, "ownDomainValue");
            $ownSubDomainValue = $this->getFieldValidation($fieldValidation, "ownSubDomainValue");

            $whatsappActive = $this->getFieldValidation($fieldValidation, "whatsappActive", false);
            $color = $this->getFieldValidation($fieldValidation, CommonText::COLOR, "#999999");
            $banners = $this->getFieldValidation($fieldValidation, CommonText::BANNERS, ["", "", ""]);
            $origin = $this->getFieldValidation($fieldValidation, "origin");
            $progress = $this->getFieldValidation($fieldValidation, "progress");
            $analytics = $this->getFieldValidation($fieldValidation, "analytics");
            $active = $this->getFieldValidation($fieldValidation, CommonText::ACTIVE_ENG);
            $currency = $this->validateCurrencyCode($fieldValidation,$origin);
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

            /** @var $catalogue Catalogo */
            if ($this->formatId($id) > 0) {


                $update = true;
                $search = new Search();
                $search->setSize(5000);
                $search->setFrom(0);
                $search->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
                $search->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);

                $query = $search->toArray();
                $query["indice"] = "catalogo";
                // consultar los datos del catalogo a elasticsearch

                $catalogueResult = $this->consultElasticSearch($query, "catalogo", false);


                if ($catalogueResult["data"] && count($catalogueResult["data"]) > 0) {
                    $catalogueData = $catalogueResult["data"][0];

                    $this->validateCatalogueExistForUpdate($name, $catalogueData->nombre, $clientId);

                    $catalogue = [
                        "fecha" => $catalogueData->fecha,
                        "fecha_actualizacion" => date("c"),
                        "estado" => $catalogueData->estado,
                        "imagen" => $catalogueData->imagen,
                        "nombre" => $catalogueData->nombre,
                        CommonText::CLIENT_ID => $catalogueData->cliente_id,
                        "id" => $catalogueData->id,
                        CommonText::BANNERS => $this->getFieldValidation((array)$catalogueData, CommonText::BANNERS, ["", "", ""]),
                        HelperEdata::EDATA_STATE_BEFORE => $catalogueData->edata_estado
                    ];

                    $catalogue["imagen"] = $this->saveImageInAWS($img, $clientId, $name, $catalogue);

                    $this->uploadBanners($catalogue, $name, $banners, $origin);

                    $this->createEpaycoProperties($catalogue, $companyName, $contactPhone, $contactEmail, $whatsappActive, $origin, $color, $indicativoPais, $progress, $active, $catalogueData, $ownDomain, $ownDomainValue, $ownSubDomainValue);


                    $this->addEpaycoProperties($catalogue,$origin,$currency, $analytics, $deliveryProviderProperties);


                } else {
                    throw new GeneralException("Catalogue not found", [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => 'Catalogue not found']]);
                }
            } else {

                $searchCatalogueExist = new Search();
                $searchCatalogueExist->setSize(5000);
                $searchCatalogueExist->setFrom(0);
                $searchCatalogueExist->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);
                $searchCatalogueExist->addQuery(new MatchQuery('nombre.keyword', $name), BoolQuery::FILTER);
                $searchCatalogueExist->addQuery(new MatchQuery('estado', true), BoolQuery::FILTER);

                $catalogueExistResult = $this->consultElasticSearch($searchCatalogueExist->toArray(), "catalogo", false);

                $this->validateCatalogueExist($catalogueExistResult);


                $timeArray = explode(" ", microtime());
                $timeArray[0] = str_replace('.', '', $timeArray[0]);

                $createDate = date("c");

                $catalogue = [
                    "id" => (int)($timeArray[1] . substr($timeArray[0], 2, 3)),
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
                            HelperEdata::EDATA_STATE => "Permitido",
                            "catalogo_id" => (int)($timeArray[1] . substr($timeArray[0], 2, 3))
                        ]
                    ],
                    CommonText::BANNERS => ["", "", ""]
                ];
                $catalogue["imagen"] = $this->saveImageInAWS($img, $clientId, $name, $catalogue);

                $this->uploadBanners($catalogue, $name, $banners, $origin);


                $this->createEpaycoProperties($catalogue, $companyName, $contactPhone, $contactEmail, $whatsappActive, $origin, $color, $indicativoPais,'', true, [], $ownDomain, $ownDomainValue, $ownSubDomainValue);
                $this->addEpaycoProperties($catalogue,$origin,$currency,$analytics, $deliveryProviderProperties);

            }

            $catalogue["nombre"] = $name;
            $catalogue[HelperEdata::EDATA_STATE] = $edata_estado;

            $this->inactiveCatalogueIfAlert($catalogue,$origin,$edata_estado);

            if (!$update) {
                $verb = "created";

                $anukisResponse = $this->elasticBulkUpload(["indice" => "catalogo", "data" => [$catalogue]]);
                
                $anukisSuccess = $anukisResponse["success"];
                $returnDate = $catalogue["fecha"];
            } else {


                $verb = "updated";
                $updateSearch = new Search();
                $updateSearch->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
                $updateSearch->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);

                $updateData = $updateSearch->toArray();
                $inlines = [
                    "ctx._source.nombre=params.name",
                    "ctx._source.imagen=params.image",
                    "ctx._source.fecha_actualizacion=params.updateDate",
                    "ctx._source.edata_estado=params.edataState",
                    "ctx._source.edata_estado_anterior=params.edataStateBefore",
                ];

                $params = [
                    "name" => $catalogue["nombre"],
                    "image" => $catalogue["imagen"],
                    "updateDate" => $catalogue["fecha_actualizacion"],
                    "edataState" => $catalogue[HelperEdata::EDATA_STATE],
                    "edataStateBefore" => $this->getEdataStateBefore($catalogue)
                ];

                $this->addEpaycoParamsForUpdate($inlines, $params, $catalogue, $finish);

                $updateData["script"] = [
                    "inline" => implode(";", $inlines),
                    "params" => $params
                ];

                $updateData["indice"] = "catalogo";


                $anukisResponse = $this->elasticUpdate($updateData);
                $anukisSuccess = $anukisResponse["success"];
                $returnDate = $catalogue["fecha_actualizacion"];
            }


            if ($anukisSuccess) {

                $newData = [
                    "id" => $catalogue["id"],
                    "name" => $catalogue["nombre"],
                    "image" => $catalogue["imagen"],
                    CommonText::CLIENTID => $catalogue[CommonText::CLIENT_ID],
                    "date" => date("Y-m-d H:i:s", strtotime($returnDate)),
                    "edataStatus" => $catalogue[HelperEdata::EDATA_STATE]
                ];

                $this->addEpaycoParamsToResponseData($newData, $catalogue, $origin,$currency);

                $success = true;
                $title_response = "Successful {$verb} catalogue";
                $text_response = "successful {$verb} catalogue";
                $last_action = "catalogue_{$verb}";
                $data = $newData;

                $this->deleteCatalogueRedis($catalogue);

                // Actualizar el registro edata con el id que se creo
                if (!empty($id_edata)) {
                    $edataSearch = new Search();
                    $edataSearch->addQuery(new MatchQuery('id', $id_edata), BoolQuery::FILTER);
                    $updateData = $edataSearch->toArray();
                    $inlines = [
                        "ctx._source.objeto.id='{$data["id"]}'",
                    ];
                    $updateData["script"] = [
                        "inline" => implode(";", $inlines)
                    ];
                    $updateData["indice"] = "edata_registro";
                    $this->elasticUpdate($updateData);
                }
            } else {
                $success = false;
                $title_response = "Error";
                $text_response = "Error {$verb} catalogue";
                $last_action = "{$verb} data in elasticsearch";
                $data = [];
            }
        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error create new catalogue";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        } catch (GeneralException $generalException) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "generalException";
            $arr_respuesta['data'] = $generalException->getData();

            return $arr_respuesta;
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        if ($edata_estado == HelperEdata::STATUS_ALERT) {
            $arr_respuesta['data']['totalErrors'] = 1;
            $arr_respuesta['data']['errors'] = [
                [
                    CommonText::COD_ERROR => 'AED100',
                    CommonText::ERROR_MESSAGE => $edata_mensaje,
                ]
            ];
        }

        return $arr_respuesta;
    }


    private function deleteCatalogueRedis ($catalogue){
        $redis =  app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_'.$catalogue["id"]);
        if ($exist) {
            $redis->del('vende_catalogue_'.$catalogue["id"]);
        }
    }

    private function handleSplitPayments($epaycoDeliveryProviderValues,$clientId) {
        if (isset($epaycoDeliveryProviderValues[0]) && $epaycoDeliveryProviderValues[0] == '472') {
            $existSplitPayment = SplitPaymentsClientsAppsMerchants::where('clienteapp_id', $clientId)->where("merchant_receiver_id", $clientId)->first();
            if (!$existSplitPayment){
                $this->saveSplitpaymentsReceiver($clientId,env('CLIENT_472_ID'));
                $this->saveSplitpaymentsReceiver($clientId,$clientId);
                $this->saveSplitPaymentsMerchants($clientId,$clientId);
            }
        }
    }

    private function saveSplitPaymentsMerchants ($clientId, $merchantId) {
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
    private function saveSplitpaymentsReceiver($clientId,$receiverId){
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
    private function getEdataStateBefore($catalogue){
        $edataStateBefore = null;
        if($catalogue[HelperEdata::EDATA_STATE] !== $catalogue[HelperEdata::EDATA_STATE_BEFORE]){
            $edataStateBefore = $catalogue[HelperEdata::EDATA_STATE_BEFORE];
        }
        return $edataStateBefore;
    }

    private function validateCatalogueExistForUpdate($newName, $oldName, $clientId)
    {
        if (getenv(CommonText::SOCIAL_SELLER_DUPLICATE_VALIDATION) && getenv(CommonText::SOCIAL_SELLER_DUPLICATE_VALIDATION) == CommonText::ACTIVE_ENG) {
            if ($oldName != $newName) {
                $searchCatalogueExist = new Search();
                $searchCatalogueExist->setSize(5000);
                $searchCatalogueExist->setFrom(0);
                $searchCatalogueExist->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);
                $searchCatalogueExist->addQuery(new MatchQuery('nombre.keyword', $newName), BoolQuery::FILTER);

                $catalogueExistResult = $this->consultElasticSearch($searchCatalogueExist->toArray(), "catalogo", false);
                if (!empty($catalogueExistResult["data"])) {
                    throw  new GeneralException(CommonText::CATALOGUE_NAME_EXIST, [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => CommonText::CATALOGUE_NAME_EXIST]]);
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
                throw  new GeneralException(CommonText::CATALOGUE_NAME_EXIST, [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => CommonText::CATALOGUE_NAME_EXIST]]);
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
            (!isset($catalogue[CommonText::BANNERS]) || (
                    $catalogue[CommonText::BANNERS][0] == "" &&
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

                $this->uploadFileAws($bucketName, $tmpfname . "." . $sacarExt[0], $imageRoute);

                unlink($tmpfname . "." . $sacarExt[0]);
            } else if (substr($img, 0, 5) == 'https') {
                $imageRoute = substr($img, strlen(getenv("AWS_BASE_PUBLIC_URL"))+1);
            }
        } else if (isset($catalogue["imagen"]) && $catalogue["imagen"] != "") {
            $imageRoute = $catalogue["imagen"];
        }

        return $imageRoute;
    }

    private function createEpaycoProperties(&$catalogue, $companyName, $contactPhone, $contactEmail, $whatsappActive, $origin, $color, $indicativoPais, $progress = '', $active = true, $catalogueData = null, $ownDomain = false, $ownDomainValue = "", $ownSubDomainValue = "")
    {

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $this->validateCompanyName($companyName);

            $this->validateDataToUpdate($catalogueData,$contactEmail,$contactPhone,$color,$indicativoPais,$whatsappActive);

            if (empty($contactPhone) && isset($catalogueData->telefono_contacto)) {
                $contactPhone = $catalogueData->telefono_contacto;
            }
            if (empty($contactEmail) && isset($catalogueData->correo_contacto)) {
                $contactEmail = $catalogueData->correo_contacto;
            }

            $catalogue[CommonText::COMPANY_NAME] = $companyName;
            $catalogue[CommonText::OWNDOMAIN] = $ownDomain;
            $catalogue[CommonText::OWNDOMAINVALUE] = isset($catalogueData->valor_dominio_propio) ? ($ownDomainValue=="" ? $catalogueData->valor_dominio_propio : $ownDomainValue) : $ownDomainValue;
            $catalogue[CommonText::OWNSUBDOMAINVALUE] = isset($catalogueData->valor_subdominio_propio) ? ($ownSubDomainValue=="" ? $catalogueData->valor_subdominio_propio : $ownSubDomainValue) : $ownSubDomainValue;
            $catalogue[CommonText::DELETE_OWNDOMAINVALUE] = $ownDomainValue=="" ? true : false;
            $catalogue[CommonText::DELETE_OWNSUBDOMAINVALUE] = $ownSubDomainValue=="" ? true : false;
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

    private function addEpaycoProperties(&$catalogue, $origin, $currency,$analitycs, $deliveryProviderProperties) {
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $catalogue[CommonText::CURRENCY] = $currency;
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

    private function validateDataToUpdate($catalogueData,$contactEmail,$contactPhone,&$color,&$indicativoPais,&$whatsappActive){
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

    private function inactiveCatalogueIfAlert(&$catalogue,$origin,$edataStatus){
        if($origin==CommonText::ORIGIN_EPAYCO && $edataStatus == HelperEdata::STATUS_ALERT){
            $catalogue[CommonText::ACTIVE] = false;
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

    private function addEpaycoParamsForUpdate(&$inlines, &$params, $catalogue, $finish)
    {

        if (isset($catalogue[CommonText::PROCEED]) && $catalogue[CommonText::PROCEED] == CommonText::ORIGIN_EPAYCO) {
            $inlines[] = "ctx._source.nombre_empresa=params.nombre_empresa";
            $inlines[] = "ctx._source.procede=params.procede";
            $inlines[] = "ctx._source.telefono_contacto=params.telefono_contacto";
            $inlines[] = "ctx._source.correo_contacto=params.correo_contacto";
            $inlines[] = "ctx._source.whatsapp_activo=params.whatsapp_activo";
            $inlines[] = "ctx._source.color=params.color";
            $inlines[] = "ctx._source.progreso=params.progreso";
            $inlines[] = "ctx._source.activo=params.activo";
            $inlines[] = "ctx._source.dominio_propio=params.dominio_propio";
            $inlines[] = "ctx._source.valor_dominio_propio=params.valor_dominio_propio";
            $inlines[] = "ctx._source.valor_subdominio_propio=params.valor_subdominio_propio";
            $inlines[] = "ctx._source.eliminado_valor_dominio_propio=params.eliminado_valor_dominio_propio";
            $inlines[] = "ctx._source.eliminado_valor_subdominio_propio=params.eliminado_valor_subdominio_propio";


            $params[CommonText::COMPANY_NAME] = $catalogue[CommonText::COMPANY_NAME];
            $params[CommonText::PROCEED] = $catalogue[CommonText::PROCEED];
            $params[CommonText::CONTACT_PHONE_ES] = $catalogue[CommonText::CONTACT_PHONE_ES];
            $params[CommonText::CONTACT_EMAIL] = $catalogue[CommonText::CONTACT_EMAIL];
            $params[CommonText::WHATSAPP_ACTIVE] = $catalogue[CommonText::WHATSAPP_ACTIVE];
            $params[CommonText::COLOR] = $catalogue[CommonText::COLOR];
            $params[CommonText::PROGRESS] = $catalogue[CommonText::PROGRESS];
            $params[CommonText::ACTIVE] = $catalogue[CommonText::ACTIVE];

            $params[CommonText::OWNDOMAIN] = $catalogue[CommonText::OWNDOMAIN];
            $params[CommonText::OWNDOMAINVALUE] = $catalogue[CommonText::OWNDOMAINVALUE];
            $params[CommonText::OWNSUBDOMAINVALUE] = $catalogue[CommonText::OWNSUBDOMAINVALUE];
            $params[CommonText::DELETE_OWNDOMAINVALUE] = $catalogue[CommonText::DELETE_OWNDOMAINVALUE];
            $params[CommonText::DELETE_OWNSUBDOMAINVALUE] = $catalogue[CommonText::DELETE_OWNSUBDOMAINVALUE];

            if (!$finish) {
                $inlines[] = "ctx._source.banners=params.banners";
                $inlines[] = "ctx._source.indicativo_pais=params.indicativo_pais";
                $inlines[] = "ctx._source.moneda=params.moneda";
                $inlines[] = "ctx._source.analiticas=params.analiticas";
                $inlines[] = "ctx._source.proveedor_envios=params.proveedor_envios";
                $inlines[] = "ctx._source.epayco_logistica=params.epayco_logistica";
                $inlines[] = "ctx._source.tipo_remitente=params.tipo_remitente";
                $inlines[] = "ctx._source.nombre_remitente=params.nombre_remitente";
                $inlines[] = "ctx._source.apellido_remitente=params.apellido_remitente";
                $inlines[] = "ctx._source.tipo_documento_remitente=params.tipo_documento_remitente";
                $inlines[] = "ctx._source.documento_remitente=params.documento_remitente";
                $inlines[] = "ctx._source.telefono_remitente=params.telefono_remitente";
                $inlines[] = "ctx._source.razon_social_remitente=params.razon_social_remitente";
                $inlines[] = "ctx._source.lista_proveedores=params.lista_proveedores";
                $inlines[] = "ctx._source.ciudad_recogida=params.ciudad_recogida";
                $inlines[] = "ctx._source.departamento_recogida=params.departamento_recogida";
                $inlines[] = "ctx._source.direccion_recogida=params.direccion_recogida";
                $inlines[] = "ctx._source.configuracion_recogida_id=params.configuracion_recogida_id";
                $inlines[] = "ctx._source.recogida_automatica=params.recogida_automatica";
                $inlines[] = "ctx._source.envio_gratis=params.envio_gratis";
                $params[CommonText::BANNERS] = $catalogue[CommonText::BANNERS];
                $params[CommonText::COUNTRY_CODE] = $catalogue[CommonText::COUNTRY_CODE];
                $params[CommonText::CURRENCY] = $catalogue[CommonText::CURRENCY];
                $params[CommonText::ANALYTICS] = $catalogue[CommonText::ANALYTICS];
                $params[CommonText::PROVIDER_DELIVERY] = $catalogue[CommonText::PROVIDER_DELIVERY];
                $params[CommonText::EPAYCO_LOGISTIC] = $catalogue[CommonText::EPAYCO_LOGISTIC];
                $params[CommonText::SENDER_TYPE] = $catalogue[CommonText::SENDER_TYPE];
                $params[CommonText::SENDER_FIRSTNAME] = $catalogue[CommonText::SENDER_FIRSTNAME];
                $params[CommonText::SENDER_LASTNAME] = $catalogue[CommonText::SENDER_LASTNAME];
                $params[CommonText::SENDER_DOC_TYPE] = $catalogue[CommonText::SENDER_DOC_TYPE];
                $params[CommonText::SENDER_DOC] = $catalogue[CommonText::SENDER_DOC];
                $params[CommonText::SENDER_PHONE] = $catalogue[CommonText::SENDER_PHONE];
                $params[CommonText::SENDER_BUSINESS] = $catalogue[CommonText::SENDER_BUSINESS];
                $params[CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES] = $catalogue[CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES];
                $params[CommonText::PICKUP_CITY] = $catalogue[CommonText::PICKUP_CITY];
                $params[CommonText::PICKUP_DEPARTAMENT] = $catalogue[CommonText::PICKUP_DEPARTAMENT];
                $params[CommonText::PICKUP_ADDRESS] = $catalogue[CommonText::PICKUP_ADDRESS];
                $params[CommonText::PICKUP_CONFIGURATION_ID] = $catalogue[CommonText::PICKUP_CONFIGURATION_ID];
                $params[CommonText::AUTOMATIC_PICKUP] = $catalogue[CommonText::AUTOMATIC_PICKUP];
                $params[CommonText::FREE_DELIVERY] = $catalogue[CommonText::FREE_DELIVERY];
            }
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

    private function formatCountryCode($fieldValidation){
        $countryCode = $this->getFieldValidation($fieldValidation, "indicativoPais");
        return str_replace("+","",$countryCode);
    }

    private function validateCurrencyCode($fieldValidation,$origin){
        $currency = $this->getFieldValidation($fieldValidation, "currency",CommonText::COP_CURRENCY_CODE);

        if($origin == CommonText::ORIGIN_EPAYCO && !in_array($currency,CommonText::STRING_CURRENCY_CODES)){
            throw new GeneralException("Invalid currency code", [[CommonText::COD_ERROR => 500, CommonText::ERROR_MESSAGE => 'Invalid currency coda']]);
        }

        return $currency;
    }
    private function getAnalyticsElasticParams($analytics){

        return [
            "facebook_pixel_active"=>$this->getFieldValidation((array)$analytics,"facebookPixelActive",false),
            "facebook_pixel_id"=>$this->getFieldValidation((array)$analytics,"facebookPixelId",""),
            "google_analytics_active"=>$this->getFieldValidation((array)$analytics,"googleAnalyticsActive",false),
            "google_analytics_id"=>$this->getFieldValidation((array)$analytics,"googleAnalyticsId",""),
            "google_tag_manager_active"=>$this->getFieldValidation((array)$analytics,"googleTagManagerActive",false),
            "google_tag_manager_id"=>$this->getFieldValidation((array)$analytics,"googleTagManagerId","")
        ];

    }

    private function getAnalyticsResponseParams($analytics){

        return [
            "facebookPixelActive"=>$this->getFieldValidation((array)$analytics,"facebook_pixel_active",false),
            "facebookPixelId"=>$this->getFieldValidation((array)$analytics,"facebook_pixel_id",""),
            "googleAnalyticsActive"=>$this->getFieldValidation((array)$analytics,"google_analytics_active",false),
            "googleAnalyticsId"=>$this->getFieldValidation((array)$analytics,"google_analytics_id",""),
            "googleTagManagerActive"=>$this->getFieldValidation((array)$analytics,"google_tag_manager_active",false),
            "googleTagManagerId"=>$this->getFieldValidation((array)$analytics,"google_tag_manager_id","")
        ];

    }

}
