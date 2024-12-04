<?php

namespace App\Listeners\ShoppingCart\Process;

use App\Events\ShoppingCart\Process\ProcessCreateShoppingCartEvent;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Models\ApifyClientes;
use App\Models\DetalleConfClientes;
use App\Models\ShoppingCart;
use App\Models\CatalogoProductos;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Compound\FunctionScoreQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\InnerHit\NestedInnerHit;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\InnerHit\ParentInnerHit;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use DateTime;
use App\Models\Clientes;
use App\Helpers\Messages\CommonText as CT;

class ProcessCreateShoppingCartListener extends HelperPago
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

    public function handle(ProcessCreateShoppingCartEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;

            $id = isset($fieldValidation["id"]) ? $fieldValidation["id"] : null;
            $catalogueId = isset($fieldValidation["catalogueId"]) ? $fieldValidation["catalogueId"] : null;
            $clientId = isset($fieldValidation["clientId"]) ? $fieldValidation["clientId"] : null;
            $state = "activo";
            $quantity = 0;
            $total = 0;
            $products = isset($fieldValidation["products"]) ? $fieldValidation["products"] : null;
            $ip = isset($fieldValidation["ip"]) ? $fieldValidation["ip"] : null;
            $update = false;
            $origin = isset($fieldValidation["origin"]) ? $fieldValidation["origin"] : null;


            $searchCatalogue = new Search();
            $searchCatalogue->setSize(1);
            $searchCatalogue->setFrom(0);
            $searchCatalogue->addQuery(new MatchQuery('id', $catalogueId), BoolQuery::FILTER);
            $searchCatalogue->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
            $searchCatalogueResult = $this->consultElasticSearch($searchCatalogue->toArray(), "catalogo", false);

            if (count($searchCatalogueResult["data"]) == 0) {
                $arr_respuesta['success'] = false;
                $arr_respuesta['titleResponse'] = "client id different than the one in the catalogue";
                $arr_respuesta['textResponse'] = "client id different than the one in the catalogue";
                $arr_respuesta['lastAction'] = "searching client id in the catalogue";
                $arr_respuesta['data'] =  [];

                return $arr_respuesta;
            }

            $searchProducts = new Search();
            $searchProducts->setSize(500);
            $searchProducts->setFrom(0);

            $boolSearchProducts = new BoolQuery();

            foreach ($products as $product) {
                $boolSearchProducts->add(new TermQuery("id", $product["id"]), BoolQuery::SHOULD);
            }

            $searchProducts->addQuery($boolSearchProducts);
            $productsResult = $this->consultElasticSearch($searchProducts->toArray(), "producto", false);
            $shoppingProducts = $productsResult["data"];

            foreach ($products as $key=>$product) {
                $productExist = false;
                foreach ($shoppingProducts as $shoppingProduct) {
                    if ($product["id"] == $shoppingProduct->id) {
                        $productExist = true;
                        $products[$key][CT::TITLE] =$shoppingProduct->titulo;
                        $products[$key][CT::PRICE] =$shoppingProduct->valor;
                        //si el producto contiene referencia se calcula el total con el valor de la referencia
                        if (isset($product["references"])) {
                            foreach ($product["references"] as $rkey=>$reference) {
                                $referenceExist = false;
                                foreach ($shoppingProduct->referencias as $shoppingReference) {
                                    if ($reference["id"] == $shoppingReference->id) {
                                        $referenceExist=true;
                                        $products[$key]["references"][$rkey][CT::TITLE] = $shoppingReference->nombre;
                                        $products[$key]["references"][$rkey][CT::PRICE] = $shoppingReference->valor;
                                        $total += $shoppingReference->valor * $reference["quantity"];
                                        $quantity += $reference["quantity"];
                                    }
                                }
                                if(!$referenceExist){
                                    $arr_respuesta['success'] = false;
                                    $arr_respuesta['titleResponse'] = "Reference " . $reference["id"] . " does not exist for product ".$product["id"];
                                    $arr_respuesta['textResponse'] = "reference does not exist";
                                    $arr_respuesta['lastAction'] = "validate_reference_exist";
                                    $arr_respuesta['data'] = [];

                                    return $arr_respuesta;
                                }
                            }
                        } else {
                            $total += $shoppingProduct->valor * $product["quantity"];
                            $quantity += $product["quantity"];
                        }
                    }
                }
                if(!$productExist){
                    $arr_respuesta['success'] = false;
                    $arr_respuesta['titleResponse'] = "Producto id " . $product["id"] . " does not exist";
                    $arr_respuesta['textResponse'] = "product does not exist";
                    $arr_respuesta['lastAction'] = "validate_product_exist";
                    $arr_respuesta['data'] = [];

                    return $arr_respuesta;
                }
            }

            if (isset($id)) {

                $update = true;
                $search = new Search();
                $search->setSize(5000);
                $search->setFrom(0);
                $search->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
                $shoppingcartResult = $this->searchGeneralElastic(["indice" => "shoppingcart", "data" => $search->toArray()]);


                if ($shoppingcartResult["data"] && isset($shoppingcartResult["data"]->hits->hits[0]->_id)) {

                    $ShoppingBefore = $shoppingcartResult["data"]->hits->hits[0]->_source->productos;
                    $shoppingcartState = $shoppingcartResult["data"]->hits->hits[0]->_source->estado;

                    if ($shoppingcartState == "eliminado" || $shoppingcartState == "abandonado") {

                        $arr_respuesta['success'] = false;
                        $arr_respuesta['titleResponse'] = "shopping cart abandoned or deleted";
                        $arr_respuesta['textResponse'] = "shopping cart abandoned or deleted";
                        $arr_respuesta['lastAction'] = "shopping cart abandoned or deleted";
                        $arr_respuesta['data'] =  "shopping cart abandoned or deleted";

                        return $arr_respuesta;
                    }


                    //Validar que exista stock suficiente para actualizar los products
                    foreach ($ShoppingBefore as $shoppingcartProduct) {
                        foreach ($shoppingProducts as $productInfo) {
                            foreach ($products as $updatedProductInfo) {
                                if ($shoppingcartProduct->id == $productInfo->id && $shoppingcartProduct->id == $updatedProductInfo["id"]) {
                                    if (isset($updatedProductInfo["references"])) {

                                        //Validar que exista stock suficiente para actualizar referencia 
                                        foreach ($shoppingcartProduct->referencias as $shoppingcartReference) {
                                            foreach ($productInfo->referencias as $productInfoReference) {
                                                foreach ($updatedProductInfo["references"] as $updatedProductInfoReference) {
                                                    if (
                                                        $shoppingcartReference->id == $productInfoReference->id
                                                        && $shoppingcartReference->id == $updatedProductInfoReference["id"]
                                                    ) {
                                                        $referenceQuantityDifference = $updatedProductInfoReference["quantity"] - $shoppingcartReference->cantidad;
                                                        if ($referenceQuantityDifference > 0 && ($productInfoReference->disponible < $referenceQuantityDifference)) {
                                                            $arr_respuesta['success'] = false;
                                                            $arr_respuesta['titleResponse'] = "Product reference " . $productInfoReference->nombre . " with insufficient stock";
                                                            $arr_respuesta['textResponse'] = "product reference with insufficient stock";
                                                            $arr_respuesta['lastAction'] = "validate_shoppingcart_stock";
                                                            $arr_respuesta['data'] = [];

                                                            return $arr_respuesta;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        $productQuantityDifference = $updatedProductInfo["quantity"] - $shoppingcartProduct->cantidad;
                                        if ($productQuantityDifference > 0 && ($productInfo->disponible < $productQuantityDifference)) {
                                            $arr_respuesta['success'] = false;
                                            $arr_respuesta['titleResponse'] = "Product " . $productInfo->titulo . " with insufficient stock";
                                            $arr_respuesta['textResponse'] = "product with insufficient stock";
                                            $arr_respuesta['lastAction'] = "validate_shoppingcart_stock";
                                            $arr_respuesta['data'] = [];

                                            return $arr_respuesta;
                                        }
                                    }
                                }
                            }
                        }
                    }



                    foreach ($ShoppingBefore as $product) {

                        if (isset($product->id) && isset($product->cantidad) && !empty($product->referencias)) {


                            foreach ($product->referencias as $productWithRef) {


                                if (isset($productWithRef->id) && isset($productWithRef->cantidad)) {

                                    $queryForUpdateStock = '{"query":{"bool":{"filter":[{"match":{"id":' . $product->id . '}}]}},"script":{"inline":"if(ctx._source.referencias !== null) {def targets = ctx._source.referencias.findAll(producto -> producto.id == params.id); for(producto in targets) { producto.disponible += params.cantidad }}","params":{"id":' . $productWithRef->id . ',     "cantidad":' . $productWithRef->cantidad . '}}}';

                                    // con el raw query aca actualizo el stock del producto con la nueva cantidad

                                    $queryObjectUpdateStock = json_decode($queryForUpdateStock);

                                    $anukisResponse = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateStock]);
                                }
                            }
                            // vaciar carrito productos sin referencia
                        } elseif (isset($product->id) && isset($product->cantidad) && empty($product->referencias)) {

                            $queryForUpdateStock = '{"script":{"source":"ctx._source.disponible += params.cantidad","params":{"cantidad":' . $product->cantidad . '}},"query":{"bool":{"filter":[{"match":{"id":{"query":' . $product->id . '}}}]}}}';

                            $queryObjectUpdateStock = json_decode($queryForUpdateStock);

                            $anukisResponse = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateStock]);
                        }
                    }


                    $shoppingcart = [];
                    $traslatedProducts = [];
                    // descontar del stock

                    foreach ($products as $key => $product) {
                        $traslatedProduct = [
                            "id" => $product["id"],
                            "cantidad" => $product["quantity"],
                            CT::POSICION => ($key + 1),
                            CT::TITULO=>$product[CT::TITLE],
                            CT::VALOR=>$product[CT::PRICE]
                        ];
                        $this->addEpaycoParamsProduct($traslatedProduct, $origin, $product);

                        if (isset($product["references"])) {
                            $productReferences = [];
                            foreach ($product["references"] as $reference) {
                                array_push($productReferences, [
                                    "cantidad" => $reference["quantity"],
                                    "id" => $reference["id"],
                                    CT::TITULO => $reference[CT::TITLE],
                                    CT::VALOR => $reference[CT::PRICE]
                                ]);
                            }
                            $traslatedProduct["referencias"] = $productReferences;
                        }
                        array_push($traslatedProducts, $traslatedProduct);
                    };

                    foreach ($traslatedProducts as $key => $product) {

                        if (isset($product["cantidad"]) && isset($product["id"]) && !empty($product["referencias"])) {

                            foreach ($product["referencias"] as $key => $reference) {

                                if (isset($reference["id"]) && isset($reference["cantidad"])) {

                                    $queryForUpdateStock = '{"query":{"bool":{"filter":[{"match":{"id":' . $product["id"] . '}}]}},"script":{"inline":"if(ctx._source.referencias !== null) {def targets = ctx._source.referencias.findAll(producto -> producto.id == params.id); for(producto in targets) { producto.disponible -= params.cantidad }}","params":{"id":' . $reference["id"] . ',     "cantidad":' . $reference["cantidad"] . '}}}';

                                    // con el raw query aca actualizo el stock del producto con la nueva cantidad

                                    $queryObjectUpdateStock = json_decode($queryForUpdateStock);

                                    $anukisResponse = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateStock]);

                                    $anukisSuccess = $anukisResponse["success"];
                                }
                            }
                        }
                    }

                    foreach ($traslatedProducts as $key => $product) {

                        if (isset($product["cantidad"]) && isset($product["id"]) && empty($product["referencias"])) {

                            $queryForUpdateStock = '{"query":{"bool":{"filter":[{"match":{"id":' . $product["id"] . '}}]}},"script":{"source":"ctx._source.disponible -= params.cantidad","params":{"cantidad":' . $product["cantidad"] . '}}}';

                            // con el raw query aca actualizo el stock del producto con la nueva cantidad

                            $queryObjectUpdateStock = json_decode($queryForUpdateStock);

                            $anukisResponse = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateStock]);

                            $anukisSuccess = $anukisResponse["success"];
                        }
                    }


                    // Crear Carrito con los productos actuales(reemplaza existentes por los nuevos)

                    $now = date("c");
                    $date = new DateTime($now);
                    $productsJson = json_encode($traslatedProducts);

                    $queryCreateShoppingcart = '{"query":{"bool":{"filter":[{"match":{"id":"' . $id . '"}}]}},"script":{"source":"ctx._source.productos = params.productos;ctx._source.fecha = params.fecha;ctx._source.total = params.total ","params":{"productos":' . $productsJson . ', "fecha":"' . $now . '","total":' . $total . '} }}';

                    $queryObjectCreateShopping = json_decode($queryCreateShoppingcart);

                    $anukisResponse = $this->updateRawQueryElastic(["indice" => "shoppingcart", "data" => $queryObjectCreateShopping]);

                    $shoppingcart = ["id" => $id, "fecha" => $date, "clienteId" => $clientId, "total" => $total, "cantidad" => $quantity, "estado" => $state, "ip" => $ip, "catalogo_id" => $catalogueId, "productos" => $traslatedProducts];
                } else {
                    $arr_respuesta['success'] = false;
                    $arr_respuesta['titleResponse'] = "shoppingcart not found";
                    $arr_respuesta['textResponse'] = "shoppingcart not found";
                    $arr_respuesta['lastAction'] = "search_shoppingcart_edit";
                    $arr_respuesta['data'] = [];

                    return $arr_respuesta;
                }
            } else {

                ///id unico ///
                $timeArray = explode(" ", microtime());
                $timeArray[0] = str_replace('.', '', $timeArray[0]);
                $token = sha1(mt_rand(1, 90000) . 'SALT');
                $idUnique =  substr($token, 0, 5) . (int) $timeArray[1] . substr($token, 5, 5);
                $id = $idUnique;
                $now = date("c");
                $date = new DateTime($now);
                $apifyClient = $this->getAlliedEntity($clientId);

                $shoppingcart = [
                    "id" => $id,
                    "fecha" => $now,
                    "clienteId" => $clientId,
                    CommonText::ENTIDAD_ALIADA => $apifyClient["alliedEntityId"],
                    CommonText::FECHA_CREACION_CLIENTE => $apifyClient["clientCreatedAt"],
                    "total" => $total,
                    "cantidad" => $quantity,
                    "estado" => $state,
                    "ip" => $ip,
                    "catalogo_id" => $catalogueId,
                    "canal_pago" => "",
                    "ultimo_estado_pago"=>CommonText::DEFAULT_LAST_TRANSACTION_SHOPPINGCART_STATUS,
                    "estado_entrega"=>CommonText::DEFAULT_SHOPPINGCART_STATUS_DELIVERY,
                    "pagos"=>[]
                ];

                if($origin == 'epayco'){
                    $shoppingcart['identificador'] = 'EPAYCO';
                }

                //Validar stock de productos para agregar al carrito

                foreach ($shoppingProducts as $productInfo) {
                    foreach ($products as $updatedProductInfo) {
                        if (isset($updatedProductInfo["references"])) {
                            foreach ($productInfo->referencias as $productInfoReference) {
                                foreach ($updatedProductInfo["references"] as $updatedProductInfoReference) {
                                    if ($productInfoReference->id == $updatedProductInfoReference["id"]) {
                                        if ($productInfoReference->disponible < $updatedProductInfoReference["quantity"]) {
                                            $arr_respuesta['success'] = false;
                                            $arr_respuesta['titleResponse'] = "Product reference " . $productInfoReference->nombre . " with insufficient stock";
                                            $arr_respuesta['textResponse'] = "product reference with insufficient stock";
                                            $arr_respuesta['lastAction'] = "validate_shoppingcart_stock";
                                            $arr_respuesta['data'] = [];

                                            return $arr_respuesta;
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($productInfo->disponible < $updatedProductInfo["quantity"]) {
                                $arr_respuesta['success'] = false;
                                $arr_respuesta['titleResponse'] = "Product " . $productInfo->titulo . " with insufficient stock";
                                $arr_respuesta['textResponse'] = "product with insufficient stock";
                                $arr_respuesta['lastAction'] = "validate_shoppingcart_stock";
                                $arr_respuesta['data'] = [];

                                return $arr_respuesta;
                            }
                        }
                    }
                }

                $traslatedProducts = [];

                foreach ($products as $key => $product) {
                    $traslatedProduct = [
                        "id" => $product["id"],
                        "cantidad" => $product["quantity"],
                        CT::TITULO=>$product[CT::TITLE],
                        CT::POSICION => ($key + 1),
                        CT::VALOR=>$product[CT::PRICE]
                    ];
                    $this->addEpaycoParamsProduct($traslatedProduct, $origin, $product);

                    if (isset($product["references"])) {
                        $productReferences = [];
                        foreach ($product["references"] as $reference) {
                            array_push($productReferences, [
                                "cantidad" => $reference["quantity"],
                                "id" => $reference["id"],
                                CT::TITULO => $reference[CT::TITLE],
                                CT::VALOR => $reference[CT::PRICE]
                            ]);
                        }
                        $traslatedProduct["referencias"] = $productReferences;
                    }
                    array_push($traslatedProducts, $traslatedProduct);
                };


                $shoppingcart["productos"] = $traslatedProducts;

                foreach ($traslatedProducts as $key => $product) {

                    if (isset($product["cantidad"]) && isset($product["id"]) && !empty($product["referencias"])) {

                        foreach ($product["referencias"] as $key => $reference) {

                            if (isset($reference["id"]) && isset($reference["cantidad"])) {

                                $queryForUpdateStock = '{"query":{"bool":{"filter":[{"match":{"id":' . $product["id"] . '}}]}},"script":{"inline":"if(ctx._source.referencias !== null) {def targets = ctx._source.referencias.findAll(producto -> producto.id == params.id); for(producto in targets) { producto.disponible -= params.cantidad }}","params":{"id":' . $reference["id"] . ',     "cantidad":' . $reference["cantidad"] . '}}}';

                                // con el raw query aca actualizo el stock del producto con la nueva cantidad

                                $queryObjectUpdateStock = json_decode($queryForUpdateStock);

                                $anukisResponse = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateStock]);

                                $anukisSuccess = $anukisResponse["success"];
                            }
                        }
                    }
                }


                foreach ($traslatedProducts as $key => $product) {

                    if (isset($product["cantidad"]) && isset($product["id"]) && empty($product["referencias"])) {


                        $queryForUpdateStock = '{"query":{"bool":{"filter":[{"match":{"id":' . $product["id"] . '}}]}},"script":{"source":"ctx._source.disponible -= params.cantidad","params":{"cantidad":' . $product["cantidad"] . '}}}';

                        // con el raw query aca actualizo el stock del producto con la nueva cantidad

                        $queryObjectUpdateStock = json_decode($queryForUpdateStock);

                        $anukisResponse = $this->updateRawQueryElastic(["indice" => "producto", "data" => $queryObjectUpdateStock]);

                        $anukisSuccess = $anukisResponse["success"];
                    }
                }
            }

            if (!$update) {

                $verb = "created";
                $shoppingCartData = $shoppingcart;
                $shoppingCartData["id"] = $id;
                $shoppingCartData["fecha"] = date("c");
                $anukisResponse = $this->elasticGeneralBulkUpload(["indice" => "shoppingcart", "data" => [$shoppingCartData]]);
            } else {
                $verb = "updated";
                $anukisSuccess = $anukisResponse["success"];
            }
            if ($anukisSuccess) {

                $responseProducts = [];

                //Consultar de nuevo los datos de productos para entregar la disponibilidad actualizada
                $searchProducts = new Search();
                $searchProducts->setSize(500);
                $searchProducts->setFrom(0);

                $boolSearchProducts = new BoolQuery();

                foreach ($products as $product) {
                    $boolSearchProducts->add(new TermQuery("id", $product["id"]), BoolQuery::SHOULD);
                }

                $searchProducts->addQuery($boolSearchProducts);
                $productsResult = $this->consultElasticSearch($searchProducts->toArray(), "producto", false);
                $shoppingProducts = $productsResult["data"];

                foreach ($shoppingcart["productos"] as $traslatedProduct) {
                    foreach ($shoppingProducts as $shoppingProduct) {
                        if ($traslatedProduct["id"] == $shoppingProduct->id) {
                            $responseProduct = [
                                "id" => $traslatedProduct["id"],
                                "quantity" => $traslatedProduct["cantidad"],
                                "position" => $traslatedProduct[CT::POSICION]
                            ];
                            $this->addEpaycoParamsProduct($responseProduct, $origin, $traslatedProduct, true);

                            if (isset($traslatedProduct["referencias"])) {
                                $responseProductReferences = [];
                                foreach ($traslatedProduct["referencias"] as $reference) {
                                    array_push($responseProductReferences, [
                                        "id" => $reference["id"],
                                        "quantity" => $reference["cantidad"]
                                    ]);
                                }
                                $responseProduct["references"] = $responseProductReferences;
                            }

                            $productData = [
                                'available' => $shoppingProduct->disponible,
                                'date' => $shoppingProduct->fecha,
                                'state' => $shoppingProduct->estado,
                                'txtCode' => $shoppingProduct->id,
                                'clientId' => $shoppingProduct->cliente_id,
                                'quantity' => $shoppingProduct->cantidad,
                                'baseTax' => $shoppingProduct->base_iva,
                                'description' => $shoppingProduct->descripcion,
                                'title' => $shoppingProduct->titulo,
                                'currency' => $shoppingProduct->moneda,
                                'urlConfirmation' => $shoppingProduct->url_confirmacion,
                                'urlResponse' => $shoppingProduct->url_respuesta,
                                'tax' => $shoppingProduct->iva,
                                'amount' => $shoppingProduct->valor,
                                'invoiceNumber' => $shoppingProduct->numerofactura,
                                'expirationDate' => $shoppingProduct->fecha_expiracion,
                                'contactName' => $shoppingProduct->nombre_contacto,
                                'contactNumber' => $shoppingProduct->numero_contacto,
                                'routeQr' => $shoppingProduct->id,
                                'routeLink' => $shoppingProduct->id,
                                'id' => $shoppingProduct->id
                            ];
                            $taxGlobal = $shoppingProduct->iva;
                            $ipoGlobal = $shoppingProduct->ipoconsumo;
                            $this->addEpaycoParams($productData, $origin, $shoppingProduct);
                            
                            
                            $this->setRelatedProducts($productData,$origin,$shoppingProduct,$responseProduct["position"],count($shoppingcart["productos"]));

                            $img = [];
                            if (isset($shoppingProduct->img) && count($shoppingProduct->img) > 0) {
                                foreach ($shoppingProduct->img as $imgPath) {
                                    array_push($img, getenv("AWS_BASE_PUBLIC_URL") . "/" . $imgPath);
                                }
                            }


                            $shippingTypes = [];
                            if (isset($shoppingProduct->envio)) {
                                foreach ($shoppingProduct->envio as $shipping) {
                                    $shippingType = [
                                        "type"=>$shipping->tipo,
                                        "amount"=>$shipping->valor
                                    ];
                                    if($shipping->tipo == "local") $shippingType["city"] = "";
                                    array_push($shippingTypes,$shippingType);
                                }
                            }

                            $references = [];
                            if (isset($shoppingProduct->referencias)) {
                                foreach ($shoppingProduct->referencias as $reference) {
                                    $imgRef = $img;
                                    if (isset($shoppingProduct->origen) && $shoppingProduct->origen === 'epayco') {
                                        $imgRef = $reference->img === "" ?
                                        $reference->img : 
                                        getenv("AWS_BASE_PUBLIC_URL") . "/" . $reference->img;
                                    }
                                    $itemRef = [
                                        'description' => $reference->descripcion,
                                        'invoiceNumber' => $reference->numerofactura,
                                        'urlResponse' => $reference->url_respuesta,
                                        'amount' => $reference->valor,
                                        'expirationDate' => $reference->fecha_expiracion,
                                        'title' => $reference->nombre,
                                        'baseTax' => $reference->base_iva,
                                        'date' => $reference->fecha,
                                        'urlConfirmation' => $reference->url_confirmacion,
                                        'routeLink' => $reference->route_link,
                                        'txtCode' => $reference->txtcodigo,
                                        'tax' => $reference->iva === 0 ? $taxGlobal : $reference->iva,
                                        'currency' => $reference->moneda,
                                        'quantity' => $reference->cantidad,
                                        'id' => $reference->id,
                                        'routeQr' => $reference->rutaqr,
                                        'available' => $reference->disponible,
                                        'img' => $imgRef
                                    ];
                                    if($origin){
                                        $itemRef['discountRate'] = isset($reference->porcentaje_descuento) ? $reference->porcentaje_descuento : 0;
                                        $itemRef['discountPrice'] = isset($reference->precio_descuento) ? $reference->precio_descuento : 0;
                                        $itemRef['netAmount'] = isset($reference->monto_neto) ? $reference->monto_neto : 0;
                                        $itemRef['consumptionTax'] = isset($reference->ipoconsumo) && $reference->ipoconsumo === 0 ? $ipoGlobal : $reference->ipoconsumo;
                                    }
                                    array_push($references, $itemRef);
                                }
                            }

                            $productData["shippingTypes"] = $shippingTypes;
                            $productData["references"] = $references;
                            $productData["categories"] = $shoppingProduct->categorias;
                            $productData["img"] = $img;

                            $responseProduct["productData"] = $productData;
                            array_push($responseProducts, $responseProduct);
                        }
                    }
                }


                $newData = [
                    "id" => $id,
                    "total" => $shoppingcart["total"],
                    "products" => $responseProducts,
                    "quantity" => $shoppingcart["cantidad"],
                    "state" => $shoppingcart["estado"],
                    "date" => $date->format("Y-m-d H:i:s"),
                    "clientId" => $clientId,
                    "catalogueId" => $shoppingcart["catalogo_id"]
                ];

                $this->addShoppingCartEpaycoParamsTax($origin,$newData);

                $success = true;
                $title_response = "Successful {$verb} shopping cart";
                $text_response = "successful {$verb} shopping cart";
                $last_action = "shopping cart {$verb}";
                $data = $newData;
            } else {
                $success = false;
                $title_response = false;
                $text_response = "Error {$verb} shopping cart";
                $last_action = "{$verb} data in elasticsearch";
                $data = [];
            }
        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error create new shopping cart";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
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

    private function addEpaycoParamsProduct(&$productData, $origin, $params, $response = false){
        if($origin === "epayco"){
            $fieldNameOperator = $response ? "operador" : "operator";
            $fieldNameValueDelivery = $response ? "valor_envio" : "valueDelivery";
            $productData[$response ? "operator" : "operador"] = CommonValidation::getFieldValidation($params, $fieldNameOperator, 0);
            $productData[$response ? "valueDelivery" : "valor_envio"] = CommonValidation::getFieldValidation($params, $fieldNameValueDelivery, "");
        }
    }

    private function addEpaycoParams(&$productData, $origin, $shoppingcartProduct){
        if($origin === "epayco"){
            $productData["discountRate"] = isset($shoppingcartProduct->porcentaje_descuento) ? $shoppingcartProduct->porcentaje_descuento : 0;
            $productData["discountPrice"] = isset($shoppingcartProduct->porcentaje_descuento) ? $shoppingcartProduct->precio_descuento : 0;
            $productData["netAmount"] = isset($shoppingcartProduct->monto_neto) ? $shoppingcartProduct->monto_neto : $shoppingcartProduct->valor;
            $productData["taxAmount"] = isset($shoppingcartProduct->iva_activo) && $shoppingcartProduct->iva_activo ? round(($shoppingcartProduct->precio_descuento * $shoppingcartProduct->iva)/100, 2) : 0;
            $productData["consumptionTaxAmount"] = isset($shoppingcartProduct->ipoconsumo_activo) && $shoppingcartProduct->ipoconsumo_activo ? round(($shoppingcartProduct->precio_descuento * $shoppingcartProduct->ipoconsumo)/100, 2) : 0;
            $productData['epaycoDeliveryProvider'] = isset($shoppingcartProduct->epayco_logistica) ? $shoppingcartProduct->epayco_logistica : false;
            $productData['epaycoDeliveryProviderValues'] = isset($shoppingcartProduct->lista_proveedores) ? $shoppingcartProduct->lista_proveedores : [];
            $productData['realWeight'] = isset($shoppingcartProduct->peso_real) ? $shoppingcartProduct->peso_real : 0;
            $productData['high'] = isset($shoppingcartProduct->alto) ? $shoppingcartProduct->alto : 0;
            $productData['long'] = isset($shoppingcartProduct->largo) ? $shoppingcartProduct->largo : 0;
            $productData['width'] = isset($shoppingcartProduct->ancho) ? $shoppingcartProduct->ancho : 0;
            $productData['declaredValue'] = isset($shoppingcartProduct->valor_declarado) ? $shoppingcartProduct->valor_declarado : 0;
        }
    }

    private function addShoppingCartEpaycoParamsTax($origin,&$shoppingCart){
        if($origin == 'epayco'){
            $consumptionTaxAmountTotal = 0;
            $taxAmountTotal = 0;
            foreach($shoppingCart["products"] as $product) {
                if (isset($product["references"]) && !empty($product["references"]) && ($product['productData']['consumptionTaxAmount'] !== 0 || $product['productData']['taxAmount'] !== 0)) {
                    $this->calculateTaxProductReferences($product, $consumptionTaxAmountTotal, $taxAmountTotal);
                } else {
                    $consumptionTaxAmountTotal += ($product["productData"]["consumptionTaxAmount"] * $product["quantity"]);
                    $taxAmountTotal += ($product["productData"]["taxAmount"] * $product["quantity"]);
                }
            }
            $shoppingCart["consumptionTaxAmountTotal"] = $consumptionTaxAmountTotal;
            $shoppingCart["taxAmountTotal"] = $taxAmountTotal;
        }
    }

    private function calculateTaxProductReferences ($product, &$consumptionTaxAmountTotal, &$taxAmountTotal) {
        foreach ($product["references"] as $reference) {
            $keyRef = array_search($reference["id"], array_column($product["productData"]["references"], 'id'));

            $consumptionTaxAmount = $product['productData']['consumptionTaxAmount'] !== 0 ? round(($product["productData"]["references"][$keyRef]["discountPrice"] * $product["productData"]["references"][$keyRef]["consumptionTax"])/100, 2) : 0;

            $taxAmount = $product['productData']['taxAmount'] !== 0 ? round(($product["productData"]["references"][$keyRef]["discountPrice"] * $product["productData"]["references"][$keyRef]["tax"])/100, 2) : 0;

            $consumptionTaxAmountTotal += ($consumptionTaxAmount * $reference["quantity"]);
            $taxAmountTotal += ($taxAmount * $reference["quantity"]);
        }
    }
            
    private function setRelatedProducts(&$productData,$origin,$shoppingProduct,$productPosition,$totalProducts){
        if($origin=="epayco" && isset($shoppingProduct->categorias[0]) && $productPosition==$totalProducts){
            $category = $shoppingProduct->categorias[0];

            $timeArray = explode(" ", microtime());
            $timeArray[0] = str_replace('.', '', $timeArray[0]);
            $randomSeed = (int) ($timeArray[1] . substr($timeArray[0], 2, 3));

            $searchRelatedProducts = new Search();
            $searchRelatedProducts->setSize(5);
            $searchRelatedProducts->setFrom(0);
            $bool = new BoolQuery();
            $bool->add(new TermQuery("estado", 1),BoolQuery::MUST);
            $bool->add(new TermQuery("categorias", $category),BoolQuery::MUST);
            $bool->add(new TermQuery("id", $shoppingProduct->id),BoolQuery::MUST_NOT);
            $functionScoreQuery = new FunctionScoreQuery($bool);
            $functionScoreQuery->addRandomFunction($randomSeed);
            $searchRelatedProducts->addQuery($functionScoreQuery);
            $relatedProductsResults = $this->consultElasticSearch($searchRelatedProducts->toArray(), "producto", false);

            $relatedProductsArray = [];

            foreach($relatedProductsResults["data"] as $relatedProduct){

                $images = [];

                foreach($relatedProduct->img as $img){
                    array_push($images,getenv("AWS_BASE_PUBLIC_URL") . '/' . $img);
                }
                array_push($relatedProductsArray,[
                    "id"=>$relatedProduct->id,
                    "netAmount"=> CommonValidation::validateIsSet((array)$relatedProduct,'monto_neto',$relatedProduct->valor,'float'),
                    "discountPrice"=> CommonValidation::validateIsSet((array)$relatedProduct,'precio_descuento',0,'float'),
                    "discountRate"=> CommonValidation::validateIsSet((array)$relatedProduct,'porcentaje_descuento',0,'float'),
                    "title"=>$relatedProduct->titulo,
                    "amount"=>$relatedProduct->valor,
                    "img"=>$images
                ]);
            }

            $productData["relatedProducts"] = $relatedProductsArray;
        }
    }
}
