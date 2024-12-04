<?php

namespace App\Service\V2\Product\Process;

use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use App\Helpers\Validation\ValidateUrlImage;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;

class ListProductService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;
    protected ProductRepository $productRepository;
    protected ShoppingCartRepository $shoppingCartRepository;
    protected ClientRepository $clientRepository;

    public function __construct(CatalogueRepository $catalogueRepository, ProductRepository $productRepository, ShoppingCartRepository $shoppingCartRepository, ClientRepository $clientRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
        $this->productRepository = $productRepository;
        $this->shoppingCartRepository = $shoppingCartRepository;
        $this->clientRepository = $clientRepository;
    }

    public function process($data)
    {

        try {

            $fieldValidation = $data;
            $clientIdString = 'cliente_id';
            $origin = true;
            $dataProductsFilter = $this->productRepository->listProductoFilter($fieldValidation);
            if (is_int($dataProductsFilter[0]->count() ?? 0) && $dataProductsFilter[0]->count() == 0) {
                $fieldValidation['mode'] = 2; //si no encontro productos usa el modo 2 de busqueda
                $dataProductsFilter = $this->productRepository->listProductoFilter($fieldValidation);
            }
            list($products, $clientId, $page, $pageSize, $totalCount) = $dataProductsFilter;
            $products = $products->toArray();
            //Subdominio
            $clientSubdomainSearch = $this->clientRepository->find($clientId);

            $clientSubdomain = isset($clientSubdomainSearch->url) ? $clientSubdomainSearch->url : "";
            $filterId = CommonValidation::getFieldValidation((array) $fieldValidation["filter"], 'id', 0);

            //Catalogos del cliente

            $catalogs = $this->catalogueRepository->findByClient($clientId);

            $catalogueName = str_replace(" ", "%20", $this->getCatalogueName($products, $catalogs));

            // //Fin consultar datos para construir url de la landing
            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'Productos consultados con exito';
            $last_action = 'successful consult';

            $routeQrString = 'routeQr';
            $routeLinkString = 'routeLink';
            $landingUrl = $this->getPathByOrigin($catalogueName, $clientSubdomain);

            list($data) = $this->listData($products, $clientSubdomain, $catalogs, $clientId, $routeQrString, $routeLinkString, $origin, $filterId);

            $paginate = [
                "current_page" => $page,
                "data" => $data,
                "from" => $page <= 1 ? 1 : ($page * $pageSize) - ($pageSize - 1),
                "last_page" => ceil($totalCount / $pageSize),
                "next_page_url" => "/catalogue?page=" . ($page + 1),
                "path" => $landingUrl,
                "per_page" => $pageSize,
                "prev_page_url" => $page <= 2 ? null : "/catalogue?pague=" . ($page - 1),
                "to" => $page <= 1 ? count($data) : ($page * $pageSize) - ($pageSize - 1) + (count($data) - 1),
                "total" => $totalCount,
            ];

        } catch (\Exception $exception) {
            $success = false;
            $last_action = 'fetch data from database' . $exception->getLine();
            $title_response = "Error" . $exception->getFile();
            $text_response = "Error query database" . $exception->getMessage();
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' =>
                $validate->errorMessage,
            );
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = isset($paginate) ? $paginate : [];

        return $arr_respuesta;
    }

    private function getCatalogueName($products, $catalogs)
    {
        $catalogueName = "";

        foreach ($products as $value) {
            foreach ($catalogs as $catalogue) {
                if (isset($value['catalogo_id']) && $catalogue->id == $value['catalogo_id']) {
                    $catalogueName = $catalogue->nombre;
                    break;
                }
            }
        }
        return $catalogueName;
    }

    private function getPathByOrigin($catalogueName, $clientSubdomain)
    {
        $path = $clientSubdomain . "/vende/" . $catalogueName;
        return $path;
    }

    public function listData($products, $clientSubdomain, $catalogs, $clientId, $routeQrString, $routeLinkString, $origin, $filterId)
    {
        $data = [];
        $this->setLastMonthSales($products);
        foreach ($products as $key => $value) {

            $catalogueName = "";
            $categoryName = "";
            $statusCategory = "Activo";
            foreach ($catalogs as $catalogue) {
                if (isset($value['catalogo_id']) && $catalogue->id == $value['catalogo_id']) {
                    $catalogueName = $catalogue->nombre;
                    $this->setCategoryNameAndStatus($categoryName, $statusCategory, $catalogue, $value);
                    break;
                }
            }

            $landingUrl = $this->getPathByOrigin($catalogueName, $clientSubdomain);

            $amount = $value['valor'];
            $netAmount = CommonValidation::getFieldValidation((array) $value, 'monto_neto', $value['valor']);
            $discountPrice = CommonValidation::getFieldValidation((array) $value, 'precio_descuento', $value['valor']);
            $discountRate = CommonValidation::getFieldValidation((array) $value, 'porcentaje_descuento', $value['valor']);
            $salePrice = ($value['porcentaje_descuento'] ?? 0) > 0 ? $value['precio_descuento'] : $value['valor'];
            if (isset($value['referencias']) && count($value['referencias']) > 0) {
                $salePrice = ($value['referencias'][0]['porcentaje_descuento'] ?? 0) > 0 ? $value['referencias'][0]['precio_descuento'] : $value['referencias'][0]['valor'];
                $amount = $value['referencias'][0]['valor'];
                $netAmount = $value['referencias'][0]['monto_neto'] ?? 0;
                $discountPrice = $value['referencias'][0]['precio_descuento'] ?? 0;
                $discountRate = $value['referencias'][0]['porcentaje_descuento'] ?? 0;
            }
            $data[$key]['discountRate'] = $discountRate;
            $data[$key]['updateDate'] = CommonValidation::getFieldValidation((array) $value, 'fecha_actualizacion', $value['fecha']);
            $data[$key]['showInventory'] = CommonValidation::getFieldValidation((array) $value, 'mostrar_inventario', false);
            $data[$key]['outstanding'] = $value['destacado'] ?? 0;
            $data[$key]['discountPrice'] = $discountPrice;
            $data[$key]['origin'] = $value['origen'] ?? "epayco";
            $data[$key]['catalogueName'] = $catalogueName;
            $data[$key]['catalogueId'] = $value['catalogo_id'];
            $data[$key]['categoryName'] = $categoryName;
            $data[$key]['statusCategory'] = $statusCategory;
            $data[$key]['active'] = !isset($value['activo']) ? true : $value['activo'];
            $data[$key]['statusProduct'] = $this->getProductStatus($data[$key]);
            $data[$key]['sales'] = $value['ventas'];
            $data[$key]['activeTax'] = CommonValidation::getFieldValidation((array) $value, 'iva_activo', false);
            $data[$key]['activeConsumptionTax'] = CommonValidation::getFieldValidation((array) $value, 'ipoconsumo_activo', false);
            $data[$key]['consumptionTax'] = CommonValidation::getFieldValidation((array) $value, 'ipoconsumo', 0);
            $data[$key]['netAmount'] = $netAmount;
            $data[$key]['salePrice'] = $salePrice;
            $data[$key]['epaycoDeliveryProvider'] = CommonValidation::getFieldValidation((array) $value, CommonText::EPAYCO_LOGISTIC, false);
            $data[$key]['epaycoDeliveryProviderValues'] = CommonValidation::getFieldValidation((array) $value, CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES, []);
            $data[$key]['realWeight'] = CommonValidation::getFieldValidation((array) $value, CommonText::REAL_WEIGHT, 0);
            $data[$key]['high'] = CommonValidation::getFieldValidation((array) $value, CommonText::HIGH, 0);
            $data[$key]['long'] = CommonValidation::getFieldValidation((array) $value, CommonText::LONG, 0);
            $data[$key]['width'] = CommonValidation::getFieldValidation((array) $value, CommonText::WIDTH, 0);
            $data[$key]['declaredValue'] = CommonValidation::getFieldValidation((array) $value, CommonText::DECLARED_VALUE, 0);
            $data[$key]['setupReferences'] = isset($value['configuraciones_referencias']) ? $this->mappingRelatedSetupReference($value['configuraciones_referencias']) : [];
            $this->setRelatedProducts($data, $key, $filterId, $value);

            $data[$key]['date'] = $value['fecha'];
            $data[$key]['state'] = $value['estado'];
            $data[$key]['txtCode'] = $value['id'];
            $data[$key]['clientId'] = $clientId;
            $data[$key]['quantity'] = $value['cantidad'];
            $data[$key]['baseTax'] = $value['base_iva'];
            $data[$key]['description'] = $value['descripcion'];
            $data[$key]['title'] = $value['titulo'];
            $data[$key]['currency'] = $value['moneda'];
            $data[$key]['urlConfirmation'] = $value['url_confirmacion'];
            $data[$key]['urlResponse'] = $value['url_respuesta'];
            $data[$key]['tax'] = $value['iva'];
            $data[$key]['amount'] = $amount;
            $data[$key]['invoiceNumber'] = $value['numerofactura'];
            $data[$key]['expirationDate'] = $value['fecha_expiracion'];
            $data[$key]['contactName'] = $value['nombre_contacto'];
            $data[$key]['contactNumber'] = $value['numero_contacto'];
            $data[$key][$routeQrString] = "http://secure2.epayco.io/apprest/printqr?txtcodigo=" . $landingUrl;
            $data[$key]['id'] = $value['id'];
            $data[$key]['lastMonthSales'] = isset($value['ventas_ultimo_mes']) ? $value['ventas_ultimo_mes'] : 0;
            $data[$key]['edataStatus'] = isset($value['edata_estado']) ? $value['edata_estado'] : "Permitido";

            if (isset($value['img'])) {
                $data[$key]['img'] = [];
                foreach ($value['img'] as $ki => $img) {
                    if (!empty($img)) {
                        $data[$key]['img'][$ki] = ValidateUrlImage::locateImage($img);
                    }
                }
            } else {
                $data[$key]['img'] = [];
            }

            if ($origin) {
                $data[$key]['firsImage'] = isset($data[$key]['img'][0]) ? $data[$key]['img'][0] : "";
            }

            $data[$key]['shippingTypes'] = [];

            if (isset($value['envio']) && count($value['envio']) > 0) {
                foreach ($value['envio'] as $kv => $env) {
                    $data[$key]['shippingTypes'][$kv]['type'] = $env->tipo;
                    $data[$key]['shippingTypes'][$kv]['amount'] = $env->valor;
                }
            }
            $categoryId = 0;
            if (isset($value['categorias']) && count($value['categorias']) > 0) {
                foreach ($value['categorias'] as $kc => $cat) {
                    $data[$key]['categories'][$kc] = $cat;
                    $categoryId = $cat;
                }
            }
            $data[$key][$routeLinkString] = $landingUrl . '/product-info/' . $categoryId . '/' . $value['id'];
            $data[$key]['references'] = [];
            if (isset($value['referencias']) && count($value['referencias']) > 0) {
                if ($value['referencias'][0]['id'] != null) {
                    $available = 0;
                    $refeences = is_array($value['referencias']) ? $value['referencias'] : (array) $value['referencias'];
                    foreach ($refeences as $kref => $ref) {
                        $data[$key]['references'][$kref]['description'] = CommonValidation::getFieldValidation($ref, 'descripcion', '');
                        $data[$key]['references'][$kref]['invoiceNumber'] = CommonValidation::getFieldValidation($ref, 'numerofactura', '');
                        $data[$key]['references'][$kref]['urlResponse'] = CommonValidation::getFieldValidation($ref, 'url_respuesta', '');
                        $data[$key]['references'][$kref]['amount'] = CommonValidation::getFieldValidation($ref, 'valor', 0);
                        $data[$key]['references'][$kref]['expirationDate'] = CommonValidation::getFieldValidation($ref, 'fecha_expiracion', '');
                        $data[$key]['references'][$kref]['title'] = CommonValidation::getFieldValidation($ref, 'nombre', '');
                        $data[$key]['references'][$kref]['baseTax'] = CommonValidation::getFieldValidation($ref, 'base_iva', 0);
                        $data[$key]['references'][$kref]['date'] = CommonValidation::getFieldValidation($ref, 'fecha', '');
                        $data[$key]['references'][$kref]['urlConfirmation'] = CommonValidation::getFieldValidation($ref, 'url_confirmacion', '');
                        $data[$key]['references'][$kref][$routeLinkString] = $data[$key][$routeLinkString];
                        $data[$key]['references'][$kref][$routeQrString] = $data[$key][$routeQrString];
                        $data[$key]['references'][$kref]['txtCode'] = CommonValidation::getFieldValidation($ref, 'txtcodigo', '');
                        $data[$key]['references'][$kref]['tax'] = CommonValidation::getFieldValidation($ref, 'iva', 0);
                        $data[$key]['references'][$kref]['currency'] = CommonValidation::getFieldValidation($ref, 'moneda', '');
                        $data[$key]['references'][$kref]['quantity'] = CommonValidation::getFieldValidation($ref, 'cantidad', 0);
                        $data[$key]['references'][$kref]['id'] = CommonValidation::getFieldValidation($ref, 'id', '');
                        $data[$key]['references'][$kref]['available'] = CommonValidation::getFieldValidation($ref, 'disponible', 0);
                        if ($origin) {
                            $data[$key]['references'][$kref]['name'] = CommonValidation::getFieldValidation($ref, 'nombre', '');
                            $data[$key]['references'][$kref]['discountRate'] = CommonValidation::getFieldValidation($ref, 'porcentaje_descuento', 0);
                            $data[$key]['references'][$kref]['discountPrice'] = CommonValidation::getFieldValidation($ref, 'precio_descuento', 0);
                            $data[$key]['references'][$kref]['netAmount'] = CommonValidation::getFieldValidation($ref, 'monto_neto', 0);
                            $data[$key]['references'][$kref]['consumptionTax'] = CommonValidation::getFieldValidation($ref, 'ipoconsumo', 0);
                        }
                        $available = $available + $ref['disponible'];
                        if (isset($ref['img']) && is_array(($ref['img']))) {
                            $referencesImg = [];
                            foreach ($ref['img'] as $referenceImg) {
                                array_push($referencesImg, ValidateUrlImage::locateImage($referenceImg));
                            }
                            $data[$key]['references'][$kref]['img'] = $referencesImg;
                        } else if (isset($ref['img'])) {
                            $data[$key]['references'][$kref]['img'] = $ref['img'] !== '' && $ref['img'] !== null ? ValidateUrlImage::locateImage($ref['img']) : null;
                        } else {
                            $data[$key]['references'][$kref]['img'] = [];
                        }
                        $data[$key]['available'] = $available;
                    }
                } else {
                    $data[$key]['available'] = $value['disponible'];
                }
            } else {
                $data[$key]['available'] = $value['disponible'];
            }
        }

        return array($data);
    }
    public function setLastMonthSales(&$products)
    {
        $productIds = array_map(function ($product) {
            return $product['id'];
        }, $products);
        $shoppingcartsWithProducts = $this->shoppingCartRepository->searchProductSeller($productIds);
        foreach ($shoppingcartsWithProducts as $shoppingcart) {
            foreach ($shoppingcart->productos as $shoppingcartProduct) {
                $matchIndex = array_search($shoppingcartProduct['id'], $productIds);
                if ($matchIndex >= 0) {
                    $this->addProductUnitSales($products, $shoppingcartProduct, $matchIndex);
                }
            }
        }
    }

    public function addProductUnitSales(&$products, $shoppingcartProduct, $invoiceMathIndex)
    {

        $unitSales = 0;

        if (isset($shoppingcartProduct['referencias'])) {
            foreach ($shoppingcartProduct['referencias'] as $reference) {
                $unitSales = $unitSales + $reference['cantidad'];
            }
        } else {
            $unitSales = $shoppingcartProduct['cantidad'];
        }

        if (isset($products[$invoiceMathIndex]['ventas_ultimo_mes'])) {
            $products[$invoiceMathIndex]['ventas_ultimo_mes'] += $unitSales;
        } else {
            $products[$invoiceMathIndex]['ventas_ultimo_mes'] = $unitSales;
        }
    }

    private function setCategoryNameAndStatus(&$categoryName, &$statusCategory, $catalogue, $value)
    {

        if (isset($value['categorias']) && !empty($value['categorias'])) {
            $categoryId = $value['categorias'][0];
            $categories = $catalogue->categorias;
            $targetCategoryIndex = array_search($categoryId, array_column((array) $categories, 'id'));
            $targetCategory = $categories[$targetCategoryIndex];
            $categoryName = $targetCategory['nombre'];
            if ($targetCategory['id'] == 1 || (isset($targetCategory['activo']) && !$targetCategory['activo'])) {
                $statusCategory = "Inactivo";
            }
        }

    }

    private function getProductStatus($product)
    {
        $status = $product['active'] === true ? "Activo" : "Inactivo";
        return $status;
    }

    private function mappingRelatedSetupReference($setupReferences)
    {
        $setup = [];
        foreach ($setupReferences as $reference) {
            array_push($setup, [
                "name" => CommonValidation::getFieldValidation((array) $reference, "nombre", ""),
                "type" => CommonValidation::getFieldValidation((array) $reference, "tipo", ""),
                "values" => CommonValidation::getFieldValidation((array) $reference, "valores", [])
            ]);
        }
        return $setup;
    }

    public function setRelatedProducts(&$data, $key, $filterId, $value)
    {
        if ($filterId > 0 && isset($value['categorias'][0])) {
            $relatedProductsResults = $this->productRepository->searchProductRelated($value['id'], $value['categorias'][0]);
            $relatedProductsResults = $relatedProductsResults->toArray();
            $relatedProductsArray = [];

            if (!empty($relatedProductsResults)) {
                $relatedProductsArray = $this->setRelatedProductsHelper($relatedProductsResults, $value);
            }

            $data[$key]["relatedProducts"] = $relatedProductsArray;

        }
    }

    private function setRelatedProductsHelper($relatedProductsResults, $value)
    {
        $relatedProductsArray = [];
        foreach ($relatedProductsResults as $relatedProduct) {

            $images = [];

            foreach ($relatedProduct['img'] as $img) {
                array_push($images, ValidateUrlImage::locateImage($img));
            }

            array_push($relatedProductsArray, [
                "id" => $relatedProduct['id'],
                "discountPrice" => CommonValidation::getFieldValidation((array) $relatedProduct, "precio_descuento", 0),
                "discountRate" => CommonValidation::getFieldValidation((array) $relatedProduct, "porcentaje_descuento", 0),
                "netAmount" => CommonValidation::getFieldValidation((array) $relatedProduct, "monto_neto", $relatedProduct['valor']),
                "epaycoDeliveryProvider" => CommonValidation::getFieldValidation((array) $value, CommonText::EPAYCO_LOGISTIC, false),
                "epaycoDeliveryProviderValues" => CommonValidation::getFieldValidation((array) $value, CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES, []),
                "realWeight" => CommonValidation::getFieldValidation((array) $value, CommonText::REAL_WEIGHT, 0),
                "high" => CommonValidation::getFieldValidation((array) $value, CommonText::HIGH, 0),
                "long" => CommonValidation::getFieldValidation((array) $value, CommonText::LONG, 0),
                "width" => CommonValidation::getFieldValidation((array) $value, CommonText::WIDTH, 0),
                "declaredValue" => CommonValidation::getFieldValidation((array) $value, CommonText::DECLARED_VALUE, 0),
                "title" => $relatedProduct['titulo'],
                "references" => isset($relatedProduct['referencias']) ? $this->mappingRelatedProductReference($relatedProduct['referencias']) : [],
                "setupReferences" => isset($relatedProduct['configuraciones_referencias']) ? $this->mappingRelatedSetupReference($relatedProduct['configuraciones_referencias']) : [],
                "amount" => $relatedProduct['valor'],
                "img" => $images,
            ]);
        }
        return $relatedProductsArray;
    }

    private function mappingRelatedProductReference($referencesData)
    {
        $relatedProducts = [];
        foreach ($referencesData as $reference) {
            array_push($relatedProducts, [
                "id" => $reference['id'],
                "discountPrice" => CommonValidation::getFieldValidation((array) $reference, "precio_descuento", 0),
                "discountRate" => CommonValidation::getFieldValidation((array) $reference, "porcentaje_descuento", 0),
                "netAmount" => CommonValidation::getFieldValidation((array) $reference, "monto_neto", $reference['valor']),
                "title" => $reference['nombre'],
                "name" => $reference['nombre'],
                "amount" => $reference['valor'],
                "img" => $reference['img'],
            ]);
        }
        return $relatedProducts;
    }

}
