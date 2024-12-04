<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Common\TransactionStateCodes;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Listeners\Services\LogisticaService;
use App\Repositories\V2\BblBuyerRepository;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use App\Repositories\V2\DiscountCodeRepository;
use App\Repositories\V2\GatewayClientRepository;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use App\Service\V2\Buyer\Process\UpdateBuyerService;
use App\Service\V2\Purchase\Process\CreatePurchaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use \Illuminate\Http\Request;

class CheckoutConfirmationShoppingCartService extends HelperPago
{

    private $shoppingCartRepository;
    private $gatewayClientRepository;
    private $discountCodeRepository;
    private $catalogueRepository;
    private $clientRepository;
    private $productRepository;
    private $buyerRepository;
    private $updateBuyerService;
    private $createPurchaseService;

    public function __construct(
        Request $request,
        ShoppingCartRepository $shoppingCartRepository,
        BblBuyerRepository $buyerRepository,
        GatewayClientRepository $gatewayClientRepository,
        DiscountCodeRepository $discountCodeRepository,
        CatalogueRepository $catalogueRepository,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
        UpdateBuyerService $updateBuyerService,
        CreatePurchaseService $createPurchaseService

    ) {
        parent::__construct($request);
        $this->shoppingCartRepository = $shoppingCartRepository;
        $this->buyerRepository = $buyerRepository;

        $this->gatewayClientRepository = $gatewayClientRepository;
        $this->discountCodeRepository = $discountCodeRepository;
        $this->catalogueRepository = $catalogueRepository;
        $this->clientRepository = $clientRepository;
        $this->updateBuyerService = $updateBuyerService;
        $this->createPurchaseService = $createPurchaseService;

        $this->productRepository = $productRepository;

    }

    public function handle($params)
    {
        try {
            $fieldValidation = $params;

            $payDate = $fieldValidation["x_fecha_transaccion"];
            $stateCode = (integer) $fieldValidation["x_cod_transaction_state"];
            $state = $fieldValidation["x_transaction_state"];
            $epaycoReference = $fieldValidation["x_ref_payco"];
            $authorization = $fieldValidation["x_approval_code"];
            $bankName = $fieldValidation["x_bank_name"];
            $totalAmount = $fieldValidation["x_amount"];
            $credit = null; // ?
            $transactionEmail = $fieldValidation["x_customer_email"];
            $franchise = $fieldValidation["x_franchise"];
            $invoinceId = (string) explode("-", $fieldValidation["x_id_invoice"])[0];

            $transactionId = $fieldValidation['x_transaction_id'];
            $currencyCode = $fieldValidation['x_currency_code'];
            $signature = $fieldValidation['x_signature'];

            $shoppingCartResult = $this->shoppingCartRepository->getById($invoinceId, 10);

            if ($this->shoppingCartIsPending($shoppingCartResult)) {

                $shoppingCartData = $shoppingCartResult[0];
                $shoppingCartQuantity = $this->getProductQuantity($shoppingCartResult[0]);

                //Buscar llaves del cliente para validar signature
                $shoppingCartClientId = $shoppingCartData->clienteId;
                $catalogueId = $shoppingCartData->catalogo_id;

                //TODO migrar llaves de los clientes epayco
                $clientData = $this->clientRepository->find($shoppingCartClientId);
                $clientDataPasarela = $this->gatewayClientRepository->findByClientId($shoppingCartClientId);

                $clientKey = $clientDataPasarela["key_cli"];
                $calculateSignature = hash('sha256', $shoppingCartClientId . '^' . $clientKey . '^' . $epaycoReference . '^' . $transactionId . '^' . $totalAmount . '^' . $currencyCode);

                if ($signature == $calculateSignature) {
                    $date = new \DateTime($payDate);

                    $payment = [
                        "fechapago" => $date->format("c"),
                        "estado" => $state,
                        "referencia_epayco" => $epaycoReference,
                        "autorizacion" => $authorization,
                        "fechatransaccion" => $date->format("c"),
                        "nombre_banco" => $bankName,
                        "valortotal" => $totalAmount,
                        "abono" => $credit,
                        "email_transaccion" => $transactionEmail,
                        "franquicia" => $franchise,
                    ];

                    list($shoppingCartStatus, $stateDelivery) = $this->getShoppingCartStatusByCode($stateCode);

                    $buyer = $this->buyerRepository->findByCriteria([
                        "correo" => $shoppingCartData["envio"]["correo"],
                        "bbl_cliente_id" => $shoppingCartClientId,
                    ]);

                    $buyer = !empty($buyer) ? $buyer->toArray() : null;

                    if ($stateCode == 1) {
                        //busco numoero registrado
                        $clientDataResponse = $this->clientRepository->find($shoppingCartClientId);

                        $sellerPhone = $clientDataResponse->telefono;
                        $indCountry = '+57';
                        //TODO migrar indicativo pais de los clientes
                        // $indCountry =  $clientDataResponse["ind_pais"];

                        //busco nombre de comercio

                        $catalogueResult = $this->catalogueRepository->getCatalogues($catalogueId, $shoppingCartClientId)[0];

                        //CREATE PURCHASE HERE

                        if($buyer)
                        {
                            $create_confirmation = $this->createPurchaseService->process([
                                "estado" => TransactionStateCodes::ACCEPTED,
                                "referencia_epayco" => $epaycoReference,
                                "carrito_id" => $shoppingCartData["id"],
                                "monto" => $totalAmount,
                                "fecha" => Carbon::now(),
                                "cantidad_productos" => $shoppingCartQuantity,
                                "bbl_comprador_id" => $buyer["id"],
                            ]);
    
                            $total_consumed = round($buyer["monto_total_consumido"] + $shoppingCartData["total"], 2);
                            $buyerData = [
                                'clientId' => $buyer['bbl_cliente_id'],
                                'email' => $buyer['correo'],
                                'firstName' => $buyer['nombre'],
                                'lastName' => $buyer['apellido'],
                                'document' => $buyer['documento'],
                                'clientPhone' => $buyer['telefono'],
                                'countryCode' => $buyer['ind_pais_tlf'],
                                'countryCode2' => $buyer['codigo_pais'],
                                'codeDane' => $buyer['codigo_dane'],
                                'country' => $buyer['pais'],
                                'department' => $buyer['departamento'],
                                'city' => $buyer['ciudad'],
                                'address' => $buyer['direccion'],
                                'other' => $buyer['otros_detalles'],
                                "lastPurchase" => $shoppingCartData["id"],
                                "totalConsumedAmount" => $total_consumed,
                                "comprador_id" => $buyer["id"],
                            ];
    
                            $update_buyer = $this->updateBuyerService->process($buyerData);
    
                        }
                       
                        $catalogue = $catalogueResult;

                        $nameShop = $catalogue->nombre;

                        $msgClient = "Usted ha realizado una compra por valor de $ " . number_format($totalAmount, 2, ',', '.') . " en $nameShop, Consulte el comprobante de la compra en su correo electrónico.";
                        $msgSell = "Usted ha recibido en su DaviPlata un pago de $ " . number_format($totalAmount, 2, ',', '.') . " por la venta de sus productos. Consulte los movimientos de sus ventas en el App DaviPlata.";

                        $this->sendSMS($msgClient, $shoppingCartData["envio"]["telefono"], $shoppingCartData["identificador"]);

                        $this->sendSMS($msgSell, $indCountry . $sellerPhone, $shoppingCartData["identificador"]);

                    } else {

                        $transactions_states = [
                            2 => TransactionStateCodes::REJECTED,
                            3 => TransactionStateCodes::PENDING,
                            4 => TransactionStateCodes::FAILED,
                            6 => TransactionStateCodes::REVERSED,
                            7 => TransactionStateCodes::HELD,
                            8 => TransactionStateCodes::INITIATED,
                            9 => TransactionStateCodes::EXPIRED,
                            10 => TransactionStateCodes::ABANDONED,
                            11 => TransactionStateCodes::CANCELLED,
                            12 => TransactionStateCodes::ANTI_FRAUD,
                        ];

                        $shoppingCartData = $shoppingCartResult[0];

                        if($buyer)
                        {

                         $create_confirmation = $this->createPurchaseService->process([
                            "estado" => $transactions_states[$stateCode],
                            "referencia_epayco" => $epaycoReference,
                            "carrito_id" => $shoppingCartData["id"],
                            "monto" => $totalAmount,
                            "fecha" => Carbon::now(),
                            "cantidad_productos" => $shoppingCartQuantity,
                            "bbl_comprador_id" => $buyer["id"],
                        ]);
                        }

                    }
                    $shoppingCart = $this->shoppingCartRepository->findById($invoinceId);

                    if (isset($shoppingCart->codigos_descuento)) {
                        $this->handleDiscountUseCodes($shoppingCart, $state);
                    }

                    if ($shoppingCart) {
                        // Se genera la guia de entrega en caso de poseer
                        list($guide, $stateDeliveryAux) = $this->guideGeneration($shoppingCart, $shoppingCartStatus, $epaycoReference, $stateDelivery, $clientData['url']);
                        $stateDelivery = $stateDeliveryAux;

                        // Update the shopping cart data
                        $shoppingCart->estado = $shoppingCartStatus;
                        $shoppingCart->ultimo_estado_pago = $state;
                        $shoppingCart->estado_entrega = $stateDelivery;
                        $shoppingCart->guia = $guide;

                        // Hacer push del objeto al arreglo de pagos dentro del carrito
                        if (!isset($shoppingCart->pagos)) {
                            $shoppingCart->pagos = [];
                        }
                        $pagos = $shoppingCart->pagos;
                        array_push($pagos, $payment);
                        // Save the changes to the database
                        $shoppingCart->pagos = $pagos;
                        $anukisUpdateShoppingCartResponse = $shoppingCart->save();

                    } else {
                        $anukisUpdateShoppingCartResponse = false;
                    }

                    $this->updateSalesField($shoppingCart,$state);
        
                    if ($anukisUpdateShoppingCartResponse) {
                        $success = true;
                        $title_response = 'Register shoppingcart checkout';
                        $text_response = 'Register shoppingcart checkout';
                        $last_action = 'register_shoppingcart_checkout';
                        $data = [];
                    } else {

                        $success = false;
                        $title_response = 'Register shoppingcart checkout failed';
                        $text_response = 'Register shoppingcart checkout failed';
                        $last_action = 'register_shoppingcart_checkout';
                        $data = [];
                    }
                } else {

                    $success = false;
                    $title_response = 'Register shoppingcart checkout failed';
                    $text_response = 'Register shoppingcart checkout failed';
                    $last_action = 'validate_signature_shoppingcart_checkout';
                    $data = [];
                }
            } else {
                $success = false;
                $title_response = 'Shoppingcart not found';
                $text_response = 'Shoppingcart not found';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }
        } catch (\Exception $exception) {
            $success = false;

            Log::info($exception->getMessage());

            $title_response = 'Error'.$exception->getMessage();
            $text_response = "Error get shopping cart".$exception->getFile();
            $last_action = 'fetch data from database'.$exception->getLine();
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

    private function getProductQuantity($shoppingCart)
    {

        $quantity = 0;

        if (isset($shoppingCart["productos"])) {
            foreach ($shoppingCart["productos"] as $products) {
                if (isset($products["referencias"])) {
                    foreach ($products["referencias"] as $references) {
                        $quantity = $quantity + $references["cantidad"];
                    }
                } else {
                    $quantity = $quantity + $products["cantidad"];

                }
            }

        }

        return $quantity;

    }

    public function shoppingCartIsPending($shoppingCartResult)
    {
        return (count($shoppingCartResult) > 0 &&
            $shoppingCartResult[0]->estado != "pagado"
            && $shoppingCartResult[0]->estado != "activo");
    }

    public function getShoppingCartStatusByCode($stateCode)
    {
        $shoppingCartStatus = "activo";
        $stateDelivery = CommonText::DEFAULT_SHOPPINGCART_STATUS_DELIVERY;
        if ($stateCode == 1) {
            $shoppingCartStatus = "pagado";
            $stateDelivery = "pendiente";
        } else if ($stateCode == 3) {
            $shoppingCartStatus = "procesando_pago";
        }
        return [$shoppingCartStatus, $stateDelivery];
    }

    public function updateSalesField($shoppingCartData, $stateTransacction)
    {
        if($stateTransacction == "Aceptada")
        {
            foreach ($shoppingCartData["productos"] as $producto) {
                if (!isset($producto["referencias"])) {
    
                    $product = $this->productRepository->find($producto["id"]);
                    if ($product) {
                        $product["ventas"] += $producto["cantidad"];
                        $product->save();
                    }
                } else {
                    foreach ($producto["referencias"] as $reference) {
    
                        if($producto && isset($producto["id"]))
                        {
                            $product = $this->productRepository->find($producto["id"]);
                            if ($product) {
                                $product["ventas"] += $reference["cantidad"];
                                $product->save();
                            }
                        }
                       
                    }
                }
            }
        }
       

    }

 

    public function sendSMS($msg, $number, $origin)
    {
        $body = [
            "number" => $number,
            "message" => $msg,
            "type" => "send",
            "origin" => "confirm-transacction-sells",
        ];
        if ($origin === 'SOCIAL_SELLER') {
            $this->apiService(
                getenv("SEND_SMS"),
                (object) $body,
                "POST"
            );
        }
    }

    public function guideGeneration($shoppingCartData, $status, $epaycoReference, $stateDelivery, $pagWeb)
    {
        $stateDeliveryAux = $stateDelivery;
        $quote = isset($shoppingCartData->cotizacion) ? (array) $shoppingCartData->cotizacion : null;

        if ($shoppingCartData->identificador === "EPAYCO" && $status === "pagado" && $quote !== null && !empty($quote) && ($quote["tcc"] !== null || $quote[472] !== null)) {
            // Instead of ElasticSearch, use the MongoDB query using the jenssegers/laravel-mongodb package.
            $catalogueData = $this->catalogueRepository->getCatalogues($shoppingCartData->catalogo_id, null, null, 10);

            $guideSuccess = ["status" => false];
            if ($catalogueData->count() > 0) {
                $this->loginElogistica();
                $guide = [];
                $logisticaService = new LogisticaService();
                $guideSuccess = $logisticaService->handleGuideGeneration($shoppingCartData, "tcc", $catalogueData->first(), $epaycoReference, $quote["tcc"], 1, $guide, "", $pagWeb);

                if ($catalogueData->first()->recogida_automatica && $quote[472] !== null && $guideSuccess["status"]) {
                    $guideSuccess = $logisticaService->handleGuideGeneration($shoppingCartData, "472", $catalogueData->first(), $epaycoReference, $quote[472], $quote[472]->id_servico, $guide, "", $pagWeb);
                    $stateDeliveryAux = "envio_programado";
                }
            }

            return [$guideSuccess["status"] ? $guide : null, $guideSuccess["status"] ? $stateDeliveryAux : $stateDelivery];
        }
        return [null, $stateDeliveryAux];
    }

    public function handleDiscountUseCodes($shoppingCartData, $stateTransacction)
    {

        // si ya antes se proceso alguna confirmación
        if (isset($shoppingCartData->pagos) && count($shoppingCartData->pagos) > 0) {

            $lastStateTransacction = $shoppingCartData->ultimo_estado_pago;
            if (($stateTransacction === 'Aceptada' || $stateTransacction === 'Pendiente') &&
                ($lastStateTransacction === 'Rechazada' || $lastStateTransacction === 'Fallida')) {
                $this->discountCodes($shoppingCartData->codigos_descuento, 'remove');
            } elseif (($stateTransacction === 'Rechazada' || $stateTransacction === 'Fallida') &&
                $lastStateTransacction === 'Pendiente') {
                $this->discountCodes($shoppingCartData->codigos_descuento, 'add');
            }
        } else {


            // si es la primera confirmación que llega
            if ($stateTransacction === 'Aceptada' || $stateTransacction === 'Pendiente') {


                $this->discountCodes($shoppingCartData->codigos_descuento, 'remove');
            }
        }

    }

    public function discountCodes($discountCodes, $action)
    {
        foreach ($discountCodes as $item) {
            $codeData = $this->discountCodeRepository->findByName($item["nombre"]);
            if ($codeData->filtro_cantidad && $codeData->cantidad_restante > 0 && $action === 'remove') {
                $codeData->cantidad_restante = $codeData->cantidad_restante - 1;
                $codeData->save();
            } elseif ($codeData->filtro_cantidad && $action === 'add') {
                $codeData->cantidad_restante = $codeData->cantidad_restante + 1;
                $codeData->save();
            }
        }
    }

}
