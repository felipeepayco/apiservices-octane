<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use App\Http\Helpers\ShoppingCart\ResponseDataService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreateShoppingCartService extends HelperPago
{

    private $productRepository;
    private $shoppingCartRepository;

    public function __construct(Request $request,
        ProductRepository $productRepository,
        ShoppingCartRepository $shoppingCartRepository
    ) {
        parent::__construct($request);
        $this->productRepository = $productRepository;
        $this->shoppingCartRepository = $shoppingCartRepository;

    }

    public function handle($params)
    {

        $fieldValidation = $params;
        $product = CommonValidation::getFieldValidation($fieldValidation,"product",null);
        $productId = CommonValidation::getFieldValidation($fieldValidation,"productId",null);
        $referenceId = CommonValidation::getFieldValidation($fieldValidation,"referenceId",null);
        $refData = CommonValidation::getFieldValidation($fieldValidation,"refData",null);
        $id = CommonValidation::getFieldValidation($fieldValidation,"id",null);
        $catalogueId = CommonValidation::getFieldValidation($fieldValidation,"catalogueId",null);
        $clientId = CommonValidation::getFieldValidation($fieldValidation,"clientId",null);
        $quantity = CommonValidation::getFieldValidation($fieldValidation,"quantity",null);
        $indexRefProduct = CommonValidation::getFieldValidation($fieldValidation,"indexRefProduct",null);
        $ip = CommonValidation::getFieldValidation($fieldValidation,"ip",null);
        $shoppingcartResult = CommonValidation::getFieldValidation($fieldValidation,"shoppingCart",null);
        $quantityTotal = 0;
        $state = "activo";
        $total = 0;
        $verb = "";
        $success = true;

        $update = $id ?  true : false;
        
        try {
            if (!$update) {
    
                ///id unico ///
                $verb = "created";
                $timeArray = explode(" ", microtime());
                $timeArray[0] = str_replace('.', '', $timeArray[0]);
                $token = sha1(mt_rand(1, 90000) . 'SALT');
                $idUnique = substr($token, 0, 5) . (int) $timeArray[1] . substr($token, 5, 5);
                $id = $idUnique;
                $apifyClient = $this->getAlliedEntity($clientId);
                $price = $referenceId ? $refData["valor"] : $product->valor;
                $now = Carbon::now()->toDateTimeString();
    
                $shoppingCart = [
                    "id" => $id,
                    "fecha" => $now,
                    "clienteId" => $clientId,
                    CommonText::ENTIDAD_ALIADA => $apifyClient["alliedEntityId"],
                    CommonText::FECHA_CREACION_CLIENTE => $apifyClient["clientCreatedAt"],
                    "total" => $price,
                    "cantidad" => $quantity,
                    "estado" => $state,
                    "ip" => $ip,
                    "catalogo_id" => $catalogueId,
                    "canal_pago" => "",
                    "ultimo_estado_pago" => CommonText::DEFAULT_LAST_TRANSACTION_SHOPPINGCART_STATUS,
                    "estado_entrega" => CommonText::DEFAULT_SHOPPINGCART_STATUS_DELIVERY,
                    "pagos" => [],
                    "identificador" => "EPAYCO"
                ];

                $traslatedProducts = [];
                $traslatedProduct = [
                    "id" => $product->id,
                    "cantidad" => $quantity,
                    "current"=> 0,
                    CommonText::TITULO => $product->titulo,
                    CommonText::POSICION => 1,
                    CommonText::VALOR => $product->valor,
                    "operador" => "",
                    "valor_envio" => 0
                ];
    
    
                if ($refData) {
                    $productReferences = [];
                    array_push($productReferences, [
                        "cantidad" => $quantity,
                        "id" => $refData["id"],
                        CommonText::TITULO => $refData["nombre"],
                        CommonText::VALOR => $refData[CommonText::VALOR],
                        "operador" => "",
                        "valor_envio" => 0
                        
                    ]);
                    $traslatedProduct["referencias"] = $productReferences;
                }
    
                array_push($traslatedProducts, $traslatedProduct);
                $shoppingCart["productos"] = $traslatedProducts;
    
                $this->shoppingCartRepository->create($shoppingCart);
                $shoppingcartResult = $shoppingCart;
                $titleResponse = "Successful create shopping cart";
                $textResponse = "Successful create shopping cart";
                $lastAction = "Successful create shopping cart";
            } else {
                $verb = "updated";
                $shoppingBefore = $shoppingcartResult->productos;
                $shoppingCartState = $shoppingcartResult->estado;
    
                if ($shoppingCartState == "eliminado" || $shoppingCartState == "abandonado") {
    
                    $arrRespuesta['success'] = false;
                    $arrRespuesta['titleResponse'] = "shopping cart abandoned or deleted";
                    $arrRespuesta['textResponse'] = "shopping cart abandoned or deleted";
                    $arrRespuesta['lastAction'] = "shopping cart abandoned or deleted";
                    $arrRespuesta['data'] = "shopping cart abandoned or deleted";
    
                    return $arrRespuesta;
                }
    
                $traslatedProducts = $shoppingBefore;
                $foundProd = false;
                //recorro los productos que estan en el carrito
                foreach ($shoppingBefore as $key => $productItemShoppingCart) {
                    // si encuentro el producto agregado en el carrito
                    if ($productItemShoppingCart["id"] == $productId) {
                        // sumo la cantidad del producto encontrado
                        $traslatedProducts[$key]["cantidad"] += $quantity;
                        // con referencia
                        if ($refData) {
                            $newReferences = $traslatedProducts[$key]["referencias"];
                            $foundRef = false;
                            //busco la referencia para ver si la referencia esta en el carrito
                            foreach($traslatedProducts[$key]["referencias"] as $key2 => $itemRef) {
                                if ($itemRef["id"] == $referenceId) { //si encuentro la referencia en el carrito sumo la cantidad
                                    $newReferences[$key2]["cantidad"] += $quantity;
                                    $foundRef = true;
                                    $total += $itemRef["valor"] * $newReferences[$key2]["cantidad"];
                                    $quantityTotal += $newReferences[$key2]["cantidad"];
                                } else { //acumulo total y cantidad de unidades
                                    $total += $itemRef["valor"] * $newReferences[$key2]["cantidad"];
                                    $quantityTotal += $newReferences[$key2]["cantidad"];
                                }
                            }
                            if (!$foundRef) { // si no encuentro la referencia entonces es una nueva referencia
                                array_push($newReferences, [
                                    "cantidad" => $quantity,
                                    "id" => $refData["id"],
                                    CommonText::TITULO => $refData["nombre"],
                                    CommonText::VALOR => $refData[CommonText::VALOR],
                                    "operador" => "",
                                    "valor_envio" => 0
                                ]);
                                $total += $refData[CommonText::VALOR] * $quantity;
                                $quantityTotal += $quantity;
                            }
                            // actualizo el array de referencia con la nueva referencia o update de la que estaba
                            $traslatedProducts[$key]["referencias"] = $newReferences;
                        } else {
                            // sin referencia ya antes se modfico la cantidad del producto y solo acumulo
                            $total+= $traslatedProducts[$key]["cantidad"] * $productItemShoppingCart["valor"];
                            $quantityTotal+= $traslatedProducts[$key]["cantidad"];
                        }
                        $foundProd = true;
                    } else { // si no es el producto en el carrito solo acumulo
                        if (isset($productItemShoppingCart["referencias"])) {
                            foreach($productItemShoppingCart["referencias"] as $key2 => $itemRef) {
                                $total += $itemRef["valor"] * $itemRef["cantidad"];
                                $quantityTotal += $itemRef["cantidad"];
                            }
                        } else {
                            $total+= $productItemShoppingCart["valor"] * $productItemShoppingCart["cantidad"];
                            $quantityTotal += $productItemShoppingCart["cantidad"];
                        }
                    }
                }
                if (!$foundProd) { //si no encuentro el producto es nuevo producto
                    $newProduct = [
                        "id" => $product->id,
                        "cantidad" => $quantity,
                        "current"=> count($shoppingBefore),
                        CommonText::TITULO => $product->titulo,
                        CommonText::POSICION => count($shoppingBefore) + 1,
                        CommonText::VALOR => $product->valor,
                        "operador" => "",
                        "valor_envio" => 0
                    ];
                    if ($refData) {
                        $productReferences = [];
                        array_push($productReferences, [
                            "cantidad" => $quantity,
                            "id" => $refData["id"],
                            CommonText::TITULO => $refData["nombre"],
                            CommonText::VALOR => $refData[CommonText::VALOR],
                            "operador" => "",
                            "valor_envio" => 0
                        ]);
                        $newProduct["referencias"] = $productReferences;
                        $total+= $refData[CommonText::VALOR] * $quantity;
                    } else {
                        $total+= $product->valor * $quantity;
                    }
                    $quantityTotal += $quantity;
                    array_push($traslatedProducts, $newProduct);
                }
    
    
    
                $now = Carbon::now()->toDateTimeString();
                $shoppingcartResult->productos = $traslatedProducts;
                $shoppingcartResult->fecha = $now;
                $shoppingcartResult->total = $total;
                $shoppingcartResult->cantidad = $quantityTotal;
                $shoppingcartResult->save();
                $titleResponse = "Successful update shopping cart";
                $textResponse = "Successful update shopping cart";
                $lastAction = "Successful update shopping cart";
    
            }
            if ($refData) {
                $product->disponible -= $quantity;
                $newRefs = $product->referencias;
                $newRefs[$indexRefProduct]["disponible"] -= $quantity;
                $product->referencias = $newRefs;
            } else {
                $product->disponible -= $quantity;
            }
            $product->save();
            $responseDataService = new ResponseDataService($this->productRepository);
            $data = $responseDataService->formateResponseData($shoppingcartResult);

        } catch (\Exception $exception) {
            $success = false;

            Log::info($exception->getMessage());

            $titleResponse = 'Error';
            $textResponse = "Error {$verb} shopping cart";
            $lastAction = 'fetch data from database';
            $data = array('totalErrors' => 1, 'errors' => $exception->getMessage());

        }

        //TODO crear carrito con un producto sin referencia verificar descuento de disponibilidad
        //TODO crear carrito con un producto con referencia verificar descuento de disponibilidad
        //TODO agregar un producto varias veces sin referencia a un carrito verificar disponibilidad y cantidad
        //TODO agregar un producto varias veces con referencia a un carrito verificar disponibilidad y cantidad

        $arrRespuesta['success'] = $success;
        $arrRespuesta['titleResponse'] = $titleResponse;
        $arrRespuesta['textResponse'] = $textResponse;
        $arrRespuesta['lastAction'] = $lastAction;
        $arrRespuesta['data'] = $data;

        return $arrRespuesta;
    }

}
