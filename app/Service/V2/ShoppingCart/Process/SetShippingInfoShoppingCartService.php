<?php

namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Messages\CommonText as CT;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\ClientShippingRepository;
use App\Repositories\V2\DiscountCodeRepository;
use App\Repositories\V2\ShoppingCartRepository;
use App\Service\V2\Buyer\Process\CreateBuyerService;
use App\Repositories\V2\BblBuyerRepository;
use Illuminate\Support\Facades\Log;
use \Illuminate\Http\Request;

class SetShippingInfoShoppingCartService extends HelperPago
{

    private $shoppingCartRepository;
    private $discountCodeRepository;
    private $clientShippingRepository;
    private $createBuyerService;
    private $buyerRepository;

    public function __construct(
        Request $request,
        ShoppingCartRepository $shoppingCartRepository,
        DiscountCodeRepository $discountCodeRepository,
        ClientShippingRepository $clientShippingRepository,
        CreateBuyerService $createBuyerService,
        BblBuyerRepository $buyerRepository,

    ) {
        parent::__construct($request);

        $this->shoppingCartRepository = $shoppingCartRepository;
        $this->discountCodeRepository = $discountCodeRepository;
        $this->clientShippingRepository = $clientShippingRepository;
        $this->createBuyerService = $createBuyerService;
        $this->buyerRepository = $buyerRepository;
    }

    public function handle($params)
    {
        try {
            $fieldValidation = $params;
            $clientId = $fieldValidation["clientId"];
            $id = (string) $fieldValidation["id"];
            $name = $fieldValidation["name"];
            $lastName = $fieldValidation["lastName"];
            $address = $fieldValidation["address"];
            $property = $fieldValidation["property"];
            $conditions = isset($fieldValidation["conditions"]) ? $fieldValidation["conditions"] : false;
            $terms = isset($fieldValidation["terms"]) ? $fieldValidation["terms"] : false;
            $phone = $fieldValidation["phone"];
            $city = isset($fieldValidation["city"]) ? $fieldValidation["city"] : "";
            $shippingAmount = $fieldValidation["shippingAmount"];
            $landingIdentifier = $fieldValidation["landingIdentifier"];
            $contactName = $fieldValidation["contactName"];
            $contactPhone = $fieldValidation["contactPhone"];
            $documentType = $this->getFieldValidation((array) $fieldValidation, "documentType", "");
            $documentNumber = $this->getFieldValidation((array) $fieldValidation, "documentNumber", "");
            $email = $fieldValidation["email"];
            $franchise = $this->getFieldValidation((array) $fieldValidation, "franchise", "");
            $ip = $fieldValidation["ip"];
            $quote = $this->getFieldValidation((array) $fieldValidation, CT::QUOTE_EN, null);
            $codeDane = $this->getFieldValidation((array) $fieldValidation, CT::CODEDANE_EN, "");
            $country = $fieldValidation["country"];
            $countryName = isset($fieldValidation["countryName"]) ? $fieldValidation["countryName"] : "";
            $countryCode = isset($fieldValidation["countryCode"]) ? $fieldValidation["countryCode"] : 57;
            $region = $fieldValidation["region"];
            $other = $fieldValidation["other"];
            $saveInfoShipping = $fieldValidation["saveInfoShipping"];
            $discountCodes = $fieldValidation["discountCodes"];
            $detailTotal = $fieldValidation["detailTotal"];
            $discountAmount = $fieldValidation["discountAmount"];
            $amount = $fieldValidation["amount"];
            $success = true;
            $data = [];
            $titleResponse = 'sucessfull shipping information';
            $textResponse = 'sucessfull shipping information';
            $lastAction = 'set_shoppingcart_shipping_info';

            if (!$conditions || !$terms) {
                $arr_respuesta['success'] = false;
                $arr_respuesta['titleResponse'] = "terms and conditions not accepted";
                $arr_respuesta['textResponse'] = "terms and conditions not accepted";
                $arr_respuesta['lastAction'] = "validate terms and conditions";
                $arr_respuesta['data'] = [];

                return $arr_respuesta;
            }

            $shippingAmount = $shippingAmount <= 0 ? 0 : $shippingAmount;
            //Validar que exista el carrito
            $shoppingCartResult = $this->searchShoppingCart($id, $clientId);
            if (!empty($shoppingCartResult)) {
                if ($shoppingCartResult) {

                    $shoppingCart = $shoppingCartResult;
                    if ($shoppingCart["estado"] === "activo") {

                        $comission = 0;
                        $dataDiscountCodes = $this->getDataDiscountCodes($discountCodes, $clientId);
                        $credit = ($shoppingCart->total + $shippingAmount) - $comission;
                        $shippingInfo = [
                            "nombre" => $name . " " . $lastName,
                            "direccion" => $address,
                            "inmueble" => $property,
                            "ciudad" => $city,
                            "valor_envio" => $shippingAmount,
                            "telefono" => $phone,
                            "terminos_condiciones" => true,
                            "tipo_document" => $documentType,
                            "numero_documento" => $documentNumber,
                            "correo" => $email,
                            CT::CODEDANE => $codeDane,
                        ];

                        $shoppingCartUpdateData = [
                            "envio" => $shippingInfo,
                            "abono" => $credit,
                            "comision" => $comission,
                            "identificador" => $landingIdentifier,
                            "numero_contacto" => $contactPhone,
                            "nombre_contacto" => $contactName,
                            "estado" => "procesando_pago",
                            "canal_pago" => $franchise,
                            "fecha" => date("c"),
                            "ip" => $ip,
                            "codigos_descuento" => $dataDiscountCodes,
                            "total_detallado" => $detailTotal,
                            "total" => $amount,
                            "total_descuentos" => $discountAmount,
                            CT::QUOTE_EN => $quote,

                        ];

                        $updateShoppingCart = $this->getUpdateShoppingCartQuery($id, $clientId, $shoppingCartUpdateData);

                        $anukisUpdateshoppingCartResponse = $this->mongoDBUpdate($updateShoppingCart);
                        $this->deleteCatalogueRedis($shoppingCart["catalogo_id"]);
                        if ($anukisUpdateshoppingCartResponse) {
                            $shoppingCartUpdated = true;
                        } else {
                            $shoppingCartUpdated = false;
                        }

                        if ($shoppingCartUpdated) {
                            $this->saveInfoShipping($saveInfoShipping, $name, $phone, $city, $address, $country, $region, $other, $contactPhone, $clientId, $shoppingCart->catalogo_id, $documentNumber, $lastName, $email, $codeDane, $countryName, $countryCode);
                        } else {
                            $success = false;
                            $titleResponse = 'Error in set shipping information';
                            $textResponse = 'Error in set shipping information';
                            $lastAction = 'set_shoppingcart_shipping_info';
                            $data = [];
                        }
                    } else {
                        $success = false;
                        $titleResponse = 'Shoppingcart is not active';
                        $textResponse = $shoppingCart["estado"];
                        $lastAction = 'consult_shopping_cart';
                        $data = [];
                    }
                } else {
                    $success = false;
                    $titleResponse = 'Shopping cart not found';
                    $textResponse = 'Shopping cart not found';
                    $lastAction = 'consult_shopping_cart';
                    $data = [];
                }
            } else {
                $success = false;
                $titleResponse = 'Unsuccessfully consult shopping cart';
                $textResponse = 'Unsuccessfully consult shopping cart';
                $lastAction = 'consult_shopping_cart';
                $data = [];
            }
        } catch (\Exception $exception) {
            $success = false;
            $titleResponse = 'Error';
            $textResponse = "Error get shopping cart";
            $lastAction = 'fetch data from database';
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
            $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $titleResponse;
        $arr_respuesta['textResponse'] = $textResponse;
        $arr_respuesta['lastAction'] = $lastAction;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    public function searchShoppingCart($id, $clientId)
    {

        $shoppingCartQuery = $this->shoppingCartRepository->findByIdAndClient($id, $clientId);

        return $shoppingCartQuery;
    }

    public function getUpdateShoppingCartQuery($id, $clientId, $data)
    {
        // Create a query to identify the shopping cart to be updated
        $query = [
            'id' => $id,
            'clienteId' => $clientId,
        ];

        // Append the update data
        $query['updateData'] = $data;

        return $query;
    }
    private function getFieldValidation($fields, $name, $default = "")
    {

        return isset($fields[$name]) ? $fields[$name] : $default;
    }
    private function saveInfoShipping($saveInfoShipping, $name, $phone, $city, $address, $country, $region, $other, $contactPhone, $clientId, $catalogoId, $documentNumber, $lastName, $email, $codeDane, $countryName, $countryCode)
    {

        if ($saveInfoShipping) {
            $firstName = $name;
            $bblClientsInfoPaymentShipping = $this->clientShippingRepository->findOrCreateByCatalogueAndEmail($catalogoId, $email);
            $bblClientsInfoPaymentShipping->bbl_cliente_id = $clientId;
            $bblClientsInfoPaymentShipping->nombre = $firstName;
            $bblClientsInfoPaymentShipping->apellido = $lastName;
            $bblClientsInfoPaymentShipping->telefono = $phone;
            $bblClientsInfoPaymentShipping->pais = $country;
            $bblClientsInfoPaymentShipping->region = $region;
            $bblClientsInfoPaymentShipping->ciudad = $city;
            $bblClientsInfoPaymentShipping->direccion = $address;
            $bblClientsInfoPaymentShipping->telefono_contacto = $contactPhone;
            $bblClientsInfoPaymentShipping->otros = $other;
            $bblClientsInfoPaymentShipping->catalogo_id = $catalogoId;
            $bblClientsInfoPaymentShipping->document_number = $documentNumber;
            $bblClientsInfoPaymentShipping->email = $email;
            $bblClientsInfoPaymentShipping->codeDane = $codeDane;
            $bblClientsInfoPaymentShipping->save();

            $bblClientsInfoBuyer = $this->buyerRepository->findOrCreateByclientIdAndEmail($clientId, $email);
            $bblClientsInfoBuyer->bbl_cliente_id = $clientId;
            $bblClientsInfoBuyer->correo = $email;
            $bblClientsInfoBuyer->nombre = $firstName;
            $bblClientsInfoBuyer->apellido = $lastName;
            $bblClientsInfoBuyer->ind_pais_tlf = $countryCode;
            $bblClientsInfoBuyer->documento = $documentNumber;
            $bblClientsInfoBuyer->telefono = $phone;
            $bblClientsInfoBuyer->pais = $countryName;
            $bblClientsInfoBuyer->codigo_pais = $country;
            $bblClientsInfoBuyer->departamento = $region;
            $bblClientsInfoBuyer->ciudad = $city;
            $bblClientsInfoBuyer->direccion = $address;
            $bblClientsInfoBuyer->codigo_dane = $codeDane;
            $bblClientsInfoBuyer->otros_detalles = $other;
            $bblClientsInfoBuyer->save();
        }
    }
    private function getDataDiscountCodes($discountCodes, $clientId)
    {
        $outDiscountCodes = [];
        if (count($discountCodes) > 0) {
            foreach ($discountCodes as $code) {

                $bblDiscountCode = $this->discountCodeRepository->findByName($code, $clientId);

                if ($bblDiscountCode->count() > 0) {
                    $dataCode = $bblDiscountCode->first();
                    $newDatacode['nombre'] = $code;
                    $newDatacode['tipo_descuento'] = $dataCode['tipo_descuento'];
                    $newDatacode['monto_descuento'] = $dataCode['monto_descuento'];
                    $outDiscountCodes[] = $newDatacode;
                }
            }
        }
        return $outDiscountCodes;
    }

    public function mongoDBUpdate($query)
    {

        $shoppingCart = $this->shoppingCartRepository->findByIdAndClient($query['id'], $query['clienteId']);

        if (!$shoppingCart) {
            return array("success" => false, "data" => []);
        }

        foreach ($query['updateData'] as $field => $value) {
            $shoppingCart->$field = $value;
        }

        $result = $shoppingCart->save();

        if ($result) {
            return array("success" => true, "data" => $shoppingCart);
        } else {
            return array("success" => false, "data" => []);
        }
    }

    private function deleteCatalogueRedis($id)
    {
        $redis = app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_' . $id);
        if ($exist) {
            $redis->del('vende_catalogue_' . $id);
        }
    }
}
