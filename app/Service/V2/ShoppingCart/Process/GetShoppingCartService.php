<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Messages\CommonText as CT;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\ClientRepository;
use App\Repositories\V2\GatewayClientRepository;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Validation\ValidateUrlImage;
use WpOrg\Requests\Requests;

class GetShoppingCartService extends HelperPago
{
    /**
     * Create the event listener.
     *
     * @return void
     */

    private $shoppingCartRepository;
    private $clientRepository;
    private $productRepository;
    private $gatewayClientRepository;

    public function __construct(
        Request $request,
        ShoppingCartRepository $shoppingCartRepository,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
        GatewayClientRepository $gatewayClientRepository

    ) {
        parent::__construct($request);

        $this->shoppingCartRepository = $shoppingCartRepository;
        $this->clientRepository = $clientRepository;
        $this->productRepository = $productRepository;
        $this->gatewayClientRepository = $gatewayClientRepository;

    }

    public function handle($params)
    {
        try {

            $fieldValidation = $params;
            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];
            # CHECK IF THE PARAMETER allClients EXIST AND IF IT IS SET TO TRUE, IF IT IS, WE CAN GET THE SHOPPING CART DETAIL EVEN IF THE CLIENT ID VALUES ARE DIFFERENT,
            # OTHERWISE WE USE THE DEFAULT CONFIGURATION
            # THIS PARAMETER IS ONLY SEND THROUGHT CONTROLLERS NOT FROM REQUEST

            $allClients = isset($fieldValidation["allClients"]) ? $fieldValidation["allClients"] : false;

            $clientDataResponse = $this->clientRepository->find($clientId);

            $sellerPhone = $clientDataResponse->telefono;

            // validar que exista el carrito
            $queryCriteria = ['id' => $id];

            if (!$allClients && env("CLIENT_ID_BABILONIA") != $clientId) {
                $queryCriteria['clienteId'] = $clientId;
            }
            $shoppingCart = $this->shoppingCartRepository->findByCriteria($queryCriteria);

            $shoppingCartResult = $shoppingCart;

            if (!empty($shoppingCartResult)) {
                if ($shoppingCartResult) {

                    $shoppingCartData = $shoppingCartResult;

                    $shoppingCart = [
                        "discountAmount" => isset($shoppingCartData["total_descuentos"]) ? $shoppingCartData["total_descuentos"] : 0,
                        "discountCodes" => isset($shoppingCartData["codigos_descuento"]) ? $shoppingCartData["codigos_descuento"] : [],
                        "detailTotal" => isset($shoppingCartData["total_detallado"]) ? $shoppingCartData["total_detallado"] : [],
                        "id" => $shoppingCartData["id"],
                        "total" => $shoppingCartData["total"],
                        "quantity" => $this->parseQuantity($shoppingCartData),
                        "state" => $shoppingCartData["estado"],
                        "date" => $shoppingCartData["fecha"],
                        "sellerPhone" => $sellerPhone,
                        CT::CHANNEL => isset($shoppingCartData["canal_pago"]) ? $shoppingCartData["canal_pago"] : "",
                        "contactName" => isset($shoppingCartData["nombre_contacto"]) ? $shoppingCartData["nombre_contacto"] : "",
                        "contactPhone" => isset($shoppingCartData["numero_contacto"]) ? $shoppingCartData["numero_contacto"] : "",
                        "clientId" => $clientId,
                    ];

                    $this->addShoppingCartEpaycoParams($shoppingCartData, $shoppingCart);

                    $commissions = [];
                    $shoppingCart["commissions"] = $commissions;
                    $shoppingCartProducts = [];
                    if (($shoppingCartData["estado"] == "activo" || $shoppingCartData["estado"] == "procesando_pago" || (isset($shoppingCartData["identificador"]) && $shoppingCartData["identificador"] == "EPAYCO")) && count($shoppingCartResult["productos"]) > 0) {

                        $productIds = collect($shoppingCartResult->productos)->pluck('id')->toArray();

                        $productsResult = $this->productRepository->findProductWhereIn($productIds, 500);

                        $shoppingProducts = $productsResult;

                        foreach ($shoppingCartResult["productos"] as $product) {

                            foreach ($shoppingProducts as $shoppingProduct) {
                                if ($product["id"] == $shoppingProduct["id"]) {
                                    $productData = [
                                        'available' => $shoppingProduct["disponible"],
                                        'date' => $shoppingProduct["fecha"],
                                        'state' => $shoppingProduct["estado"],
                                        'txtCode' => $shoppingProduct["id"],
                                        'clientId' => $shoppingProduct["cliente_id"],
                                        'quantity' => $shoppingProduct["cantidad"],
                                        'baseTax' => $shoppingProduct["base_iva"],
                                        'description' => $shoppingProduct["descripcion"],
                                        'title' => $shoppingProduct["titulo"],
                                        'currency' => $shoppingProduct["moneda"],
                                        'urlConfirmation' => $shoppingProduct["url_confirmacion"],
                                        'urlResponse' => $shoppingProduct["url_respuesta"],
                                        'tax' => $shoppingProduct["iva"],
                                        'consumptionTax' => $shoppingProduct["ipoconsumo"],
                                        'amount' => $shoppingProduct["valor"],
                                        'invoiceNumber' => $shoppingProduct["numerofactura"],
                                        'expirationDate' => $shoppingProduct["fecha_expiracion"],
                                        'contactName' => $shoppingProduct["nombre_contacto"],
                                        'contactNumber' => $shoppingProduct["numero_contacto"],
                                        'routeQr' => $shoppingProduct["id"],
                                        'routeLink' => $shoppingProduct["id"],
                                        'id' => $shoppingProduct["id"],
                                    ];

                                    $taxGlobal = $shoppingProduct->iva;
                                    $ipoGlobal = $shoppingProduct->ipoconsumo;

                                    $this->addProductEpaycoParams($productData, $shoppingCartData, $shoppingProduct);
                                    $img = [];
                                    if (isset($shoppingProduct->img) && count($shoppingProduct->img) > 0) {
                                        foreach ($shoppingProduct->img as $imgPath) {
                                            array_push($img, ValidateUrlImage::locateImage($imgPath));
                                        }
                                    }

                                    $shippingTypes = [];
                                    if (isset($shoppingProduct->envio)) {
                                        foreach ($shoppingProduct->envio as $shipping) {
                                            $shippingType = [
                                                "type" => $shipping->tipo,
                                                "amount" => $shipping->valor,
                                            ];
                                            if ($shipping->tipo == "local") {
                                                $shippingType["city"] = "";
                                            }
                                            array_push($shippingTypes, $shippingType);
                                        }
                                    }

                                    $references = [];
                                    if (isset($shoppingProduct["referencias"])) {

                                        if (count($shoppingProduct["referencias"])) {
                                            foreach ($shoppingProduct["referencias"] as $keyRef => $reference) {
                                                $imgRef = $img;

                                                if (isset($shoppingCartData["identificador"]) && $shoppingCartData["identificador"] === 'EPAYCO') {
                                                    $imgRef = $reference["img"] === "" ? $reference["img"] : 
                                                    ValidateUrlImage::locateImage($reference["img"]);
                                                }
                                                
                                                $consumption = $ipoGlobal;

                                                if(isset($reference["consumptionTax"]))
                                                {
                                                    $consumption = $reference["consumptionTax"] === 0 ? $ipoGlobal : $reference["consumptionTax"];
                                                }

                                                $iva = $taxGlobal;

                                                if(isset($reference["iva"]))
                                                {
                                                    $iva =  $reference["iva"] === 0 ? $taxGlobal : $reference["iva"];
                                                }

                                                array_push($references, [
                                                    'description' => $reference["descripcion"],
                                                    'invoiceNumber' => $reference["numerofactura"],
                                                    'urlResponse' => $reference["url_respuesta"],
                                                    'amount' => $reference["valor"],
                                                    'expirationDate' => $reference["fecha_expiracion"],
                                                    'title' => $reference["nombre"],
                                                    'baseTax' => $reference["base_iva"],
                                                    'date' => $reference["fecha"],
                                                    'urlConfirmation' => $reference["url_confirmacion"],
                                                    'routeLink' => $reference["route_link"],
                                                    'txtCode' => $reference["txtcodigo"],
                                                    'tax' =>$iva,
                                                    'consumptionTax' => $consumption,
                                                    'currency' => $reference["moneda"],
                                                    'quantity' => $reference["cantidad"],
                                                    'id' => $reference["id"],
                                                    'routeQr' => $reference["rutaqr"],
                                                    'available' => $reference["disponible"],
                                                    'img' => $imgRef,
                                                ]);

                                                $this->addShoppingCartEpaycoParamsRefecence($references, $reference, $keyRef, isset($shoppingCartData["identificador"]) ? $shoppingCartData["identificador"] : null, $ipoGlobal);
                                            }
                                        }

                                    }
                                    $productData["shippingTypes"] = $shippingTypes;
                                    $productData["references"] = $references;
                                    $productData["categories"] = $shoppingProduct->categorias;
                                    $productData["img"] = $img;

                                    $shoppingCartProduct = [
                                        "id" => $product["id"],
                                        "quantity" => $product["cantidad"],
                                        "productData" => $productData,
                                        "position" => $product["posicion"],
                                    ];

                                    if (!isset($product["referencias"])) {
                                        $shoppingCartProduct["initialQuantity"] = $product["cantidad"];
                                    }

                                    $this->addEpaycoParamsShoppingCartProduct($shoppingCartProduct, $shoppingCartData, $product);

                                    if (isset($product["referencias"])) {

                                    
                                        if (count($product["referencias"])) {
                                            $references = [];
                                            foreach ($product["referencias"] as $reference) {

                                                array_push($references, [
                                                    "id" => $reference["id"],
                                                    "quantity" => $reference["cantidad"],
                                                    "initialQuantity"=> $reference["cantidad"]

                                                ]);
                                            }
                                            $shoppingCartProduct["references"] = $references;
                                        }

                                    }

                                    array_push($shoppingCartProducts, $shoppingCartProduct);

                                }

                            }

                        }
                    }

                    $shoppingCart["products"] = $shoppingCartProducts;

                    $this->addShoppingCartEpaycoParamsTax($shoppingCartData, $shoppingCart, $clientId);

                    $success = true;
                    $title_response = 'List Shopping cart';
                    $text_response = 'List Shopping cart';
                    $last_action = 'shopping_cart';
                    $data = $shoppingCart;

                } else {
                    $success = false;
                    $title_response = 'Shopping cart not found';
                    $text_response = 'Shopping cart not found';
                    $last_action = 'consult_shopping_cart';
                    $data = [];
                }
            } else {
                $success = false;
                $title_response = 'Unsuccessfully consult shopping cart';
                $text_response = 'Unsuccessfully consult shopping cart';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }

        } catch (\Exception $exception) {

            Log::info($exception);
            $success = false;
            $title_response = 'Error';
            $text_response = "Error get shopping cart";
            $last_action = 'fetch data from database';
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    //PARSE PRODUCT QUANTITY
    public function parseQuantity($car)
    {
        $quantity = 0;

        foreach ($car["productos"] as $product) {
            if (isset($product["referencias"])) {
                foreach ($product["referencias"] as $key => $value) {
                    $quantity += $value["cantidad"];
                }
            } else {
                $quantity += $product["cantidad"];
            }
        }

        return $quantity;
    }

    private function addShoppingCartEpaycoParamsRefecence(&$references, $reference, $key, $origin, $ipoGlobal = 0)
    {
            $references[$key]["discountRate"] = $reference["porcentaje_descuento"];
            $references[$key]["discountPrice"] = $reference["precio_descuento"];
            $references[$key]["netAmount"] = $reference["monto_neto"];
            $references[$key]["consumptionTax"] = $reference["ipoconsumo"] === 0 ? $ipoGlobal : $reference["ipoconsumo"];
            $references[$key]["taxAmount"] = $reference["iva"] ? round(($reference["precio_descuento"] * $reference["iva"]) / 100, 2) : 0;
            $references[$key]["consumptionTaxAmount"] = isset($reference["ipoconsumo"]) && $reference["ipoconsumo"] ? round(($reference["precio_descuento"] * $reference["ipoconsumo"]) / 100, 2) : 0;
    }

    private function addEpaycoParamsShoppingCartProduct(&$productData, $shoppingCartData, $params)
    {

    
        if (isset($shoppingCartData["identificador"]) && $shoppingCartData["identificador"] == "EPAYCO") {
            $productData["operator"] = isset($params["operador"]) ? $params["operador"] : "";
            $productData["valueDelivery"] = isset($params["valor_envio"]) ? $params["valor_envio"] : 0;
        }
    }

    private function addProductEpaycoParams(&$productData, $shoppingCartData, $shoppingProduct)
    {
        if (isset($shoppingCartData->identificador) && $shoppingCartData->identificador == "EPAYCO") {
            $productData["discountRate"] = isset($shoppingProduct->porcentaje_descuento) ? $shoppingProduct->porcentaje_descuento : 0;
            $productData["discountPrice"] = isset($shoppingProduct->precio_descuento) ? $shoppingProduct->precio_descuento : 0;
            $productData["netAmount"] = isset($shoppingProduct->monto_neto) ? $shoppingProduct->monto_neto : $shoppingProduct->valor;
            $productData["taxAmount"] = isset($shoppingProduct->iva_activo) ? round(($shoppingProduct->precio_descuento * $shoppingProduct->iva) / 100, 2) : 0;
            $productData["consumptionTaxAmount"] = isset($shoppingProduct->ipoconsumo_activo) ? round(($shoppingProduct->precio_descuento * $shoppingProduct->ipoconsumo) / 100, 2) : 0;
            $productData['epaycoDeliveryProvider'] = isset($shoppingProduct->epayco_logistica) ? $shoppingProduct->epayco_logistica : false;
            $productData['epaycoDeliveryProviderValues'] = isset($shoppingProduct->lista_proveedores) ? $shoppingProduct->lista_proveedores : [];
            $productData['realWeight'] = isset($shoppingProduct->peso_real) ? $shoppingProduct->peso_real : 0;
            $productData['high'] = isset($shoppingProduct->alto) ? $shoppingProduct->alto : 0;
            $productData['long'] = isset($shoppingProduct->largo) ? $shoppingProduct->largo : 0;
            $productData['width'] = isset($shoppingProduct->ancho) ? $shoppingProduct->ancho : 0;
            $productData['declaredValue'] = isset($shoppingProduct->valor_declarado) ? $shoppingProduct->valor_declarado : 0;
        }
    }

    private function addShoppingCartEpaycoParams($shoppingCartData, &$shoppingCart)
    {
        if (isset($shoppingCartData["identificador"]) && $shoppingCartData["identificador"] == "EPAYCO") {
            $shoppingCart["catalogueId"] = $shoppingCartData["catalogo_id"];
            $shoppingCart["address"] = isset($shoppingCartData["envio"]) ? $shoppingCartData["envio"]["direccion"] : "";
            $shoppingCart["city"] = isset($shoppingCartData["envio"]) ? $shoppingCartData["envio"]["ciudad"] : "";
            $shoppingCart["email"] = isset($shoppingCartData["envio"]) ? $shoppingCartData["envio"]["correo"] : "";
            $shoppingCart["name"] = isset($shoppingCartData["envio"]) ? $shoppingCartData["envio"]["nombre"] : "";
            $shoppingCart[CT::CODEDANE_EN] = isset($shoppingCartData["envio"]) && isset($shoppingCartData["envio"]["codigo_dane"]) ? $shoppingCartData["envio"]["codigo_dane"] : "";
            $shoppingCart['statePay'] = isset($shoppingCartData["ultimo_estado_pago"]) ? $shoppingCartData["ultimo_estado_pago"] : CT::DEFAULT_LAST_TRANSACTION_SHOPPINGCART_STATUS;
            $shoppingCart['stateDelivery'] = isset($shoppingCartData["estado_entrega"]) ? $shoppingCartData["estado_entrega"] : CT::DEFAULT_SHOPPINGCART_STATUS_DELIVERY;
            $shoppingCart["guide"] = isset($shoppingCartData["guia"]) ? $shoppingCartData["guia"] : null;
            $shoppingCart["pickup"] = isset($shoppingCartData["entrega"]) ? $shoppingCartData["entrega"] : null;
            $shoppingCart[CT::QUOTE_EN] = isset($shoppingCartData["cotizacion"]) ? $shoppingCartData["cotizacion"] : null;
        }
    }

    private function addShoppingCartEpaycoParamsTax($shoppingCartData, &$shoppingCart, $clientId)
    {

        try {
            if (isset($shoppingCartData["identificador"]) && $shoppingCartData["identificador"] == "EPAYCO") {
                $consumptionTaxAmountTotal = 0;
                $taxAmountTotal = 0;
                foreach ($shoppingCart["products"] as $product) {
                    list($consumptionTaxAmount, $taxAmount) = $this->getTaxAmountProduct($product);
                    if (isset($product["references"]) && !empty($product["references"]) && ($product['productData']['consumptionTaxAmount'] !== 0 || $product['productData']['taxAmount'] !== 0)) {
                        $this->calculateTaxProductReferences($product, $consumptionTaxAmountTotal, $taxAmountTotal);
                    } else {
                        $consumptionTaxAmountTotal += ($consumptionTaxAmount * $product["quantity"]);
                        $taxAmountTotal += ($taxAmount * $product["quantity"]);
                    }
                }
                $shoppingCart["consumptionTaxAmountTotal"] = $consumptionTaxAmountTotal;
                $shoppingCart["taxAmountTotal"] = $taxAmountTotal;

                if ($shoppingCart["statePay"] === "Aceptada" || $shoppingCart["statePay"] === "Pendiente" || $shoppingCart["statePay"] === "Rechazada") {

                    $client_data = $this->gatewayClientRepository->findByClientId($clientId);

                    $public_key = $client_data->public_key;
                    $private_key = $client_data->private_key;
                    $this->loginApify($public_key, $private_key);
                    $url = config("app.BASE_URL_APIFY") . "/transaction/detail";
                    $headers = ["authorization" => "Bearer {$this->tokenApify}"];

                    $data = [];
                    $invoice_data = [];

                    if (isset($shoppingCartData['pagos']) && count($shoppingCartData['pagos'])) {

                        $pagos = collect($shoppingCartData['pagos'])->where('estado', 'Aceptada')->first();

                        if (empty($pagos)) {
                            $pagos = collect($shoppingCartData['pagos'])->where('estado', 'Pendiente')->first();

                        }
                        if (empty($pagos)) {
                            $pagos = collect($shoppingCartData['pagos'])->last();
                        }

                        if (!empty($pagos)) {
                            $data["filter"]["referencePayco"] = (int) $pagos["referencia_epayco"];
                            $invoice_response = Requests::post($url, $headers, $data, [['timeout' => 120, 'connect_timeout' => 120]]);
                            if ($invoice_response->status_code == "200" || $invoice_response->status_code == "100") {
                                $body = json_decode($invoice_response->body, true);
                                unset($body["data"]["log"]);
                                unset($body["data"]["allLogs"]);

                                $invoice_data = $body["data"];
                            }
                        }

                    }
                    $shoppingCart["invoice"] = $invoice_data;
                    $shoppingCart["invoice"]["id"] = $shoppingCartData["id"];
                    $shoppingCart["invoice"]["id_cliente"] = $clientId;

                    unset($shoppingCart["invoice"]["ip_transaccion"]);
                    unset($shoppingCart["invoice"]["id_cliente_facturar"]);
                    unset($shoppingCart["invoice"]["id_entidad_aliada"]);
                    unset($shoppingCart["invoice"]["autorizacion"]);
                }
            }
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());

        }
    }

    private function calculateTaxProductReferences($product, &$consumptionTaxAmountTotal, &$taxAmountTotal)
    {
        foreach ($product["references"] as $reference) {
            $keyRef = array_search($reference["id"], array_column($product["productData"]["references"], 'id'));

            $consumptionTaxAmount = $product['productData']['consumptionTaxAmount'] !== 0 ? round(($product["productData"]["references"][$keyRef]["discountPrice"] * $product["productData"]["references"][$keyRef]["consumptionTax"]) / 100, 2) : 0;

            $taxAmount = $product['productData']['taxAmount'] !== 0 ? round(($product["productData"]["references"][$keyRef]["discountPrice"] * $product["productData"]["references"][$keyRef]["tax"]) / 100, 2) : 0;

            $consumptionTaxAmountTotal += ($consumptionTaxAmount * $reference["quantity"]);
            $taxAmountTotal += ($taxAmount * $reference["quantity"]);
        }
    }

    private function getTaxAmountProduct($product)
    {
        $consumptionTaxAmount = $product["productData"]["consumptionTaxAmount"];
        $taxAmount = $product["productData"]["taxAmount"];
        if (isset($product["references"]) && count($product["references"]) > 0) {
            foreach ($product["references"] as $reference) {
                $keyRef = array_search($reference["id"], array_column($product["productData"]["references"], 'id'));
                $consumptionTaxAmount = $product["productData"]["consumptionTaxAmount"] != 0 ? round(($product["productData"]["references"][$keyRef]["discountPrice"] * $product["productData"]["references"][$keyRef]["consumptionTax"]) / 100, 2) * $reference["quantity"] : 0;
                $taxAmount = $product["productData"]["taxAmount"] != 0 ? round(($product["productData"]["references"][$keyRef]["discountPrice"] * $product["productData"]["references"][$keyRef]["tax"]) / 100, 2) * $reference["quantity"] : 0;
            }
        }
        return [$consumptionTaxAmount, $taxAmount];
    }

}
