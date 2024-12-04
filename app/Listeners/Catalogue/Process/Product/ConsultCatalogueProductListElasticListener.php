<?php

namespace App\Listeners\Catalogue\Process\Product;


use App\Events\ConsultCatalogueProductListElasticEvent;
use App\Helpers\Edata\HelperEdata;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;

use App\Listeners\Services\VendeConfigPlanService;
use App\Models\BblClientes;
use Illuminate\Http\Request;

use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Compound\FunctionScoreQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class ConsultCatalogueProductListElasticListener extends HelperPago
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

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ConsultCatalogueProductListElasticEvent $event)
    {

        try {

            $fieldValidation = $event->arr_parametros;

            list($invoices,$page,$pageSize,$clientId,$clientIdString,$origin) = $this->listProducto($fieldValidation);

            //Subdominio
            $clientSubdomainSearch = BblClientes::find($clientId);

            $clientSubdomain = isset($clientSubdomainSearch->url) ? $clientSubdomainSearch->url : "";
            $filterId = $this->getFieldValidation((array)$fieldValidation["filter"],'id',0);

            //Catalogos del cliente
            $searchCatalogue = new Search();
            $searchCatalogue->setSize(5000);
            $searchCatalogue->setFrom(0);
            $searchCatalogue->addQuery(new MatchQuery($clientIdString, $clientId), BoolQuery::FILTER);
            $catalogueResult = $this->consultElasticSearch($searchCatalogue->toArray(), "catalogo", false);
            $catalogs = $catalogueResult["data"];

            $catalogueName = str_replace(" ","%20",$this->getCatalogueName($invoices,$catalogs));

            //Fin consultar datos para construir url de la landing
            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'Productos consultados con exito';
            $last_action = 'successful consult';

            $routeQrString = 'routeQr';
            $routeLinkString = 'routeLink';
            $landingUrl = $this->getPathByOrigin($origin,$catalogueName,$clientSubdomain);

            list($data) = $this->listData($invoices,$clientSubdomain,$catalogs,$clientId,$routeQrString,$routeLinkString,$origin,$filterId);
            $paginate = [
                "current_page" => $page,
                "data" => $data,
                "from" => $page <= 1 ? 1 : ($page * $pageSize) - ($pageSize - 1),
                "last_page" => ceil($invoices['pagination']['totalCount']/$pageSize),
                "next_page_url" => "/catalogue?page=" . ($page + 1),
                "path" => $landingUrl,
                "per_page" => $pageSize,
                "prev_page_url" => $page <= 2 ? null : "/catalogue?pague=" . ($page - 1),
                "to" => $page <= 1 ? count($data) : ($page * $pageSize) - ($pageSize - 1) + (count($data) - 1),
                "total" => $invoices['pagination']['totalCount']
            ];


        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error '.$exception->getMessage();
            $text_response = "Error query to database";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }


        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = isset($paginate)?$paginate:[];

        return $arr_respuesta;
    }

    private function getPathByOrigin($origin,$catalogueName,$clientSubdomain){
        $path = $clientSubdomain."/catalogo/".$catalogueName."/detalle-producto/";
        if($origin){
            $path = $clientSubdomain."/vende/";
        }

        return $path;
    }

    public function listProducto($fieldValidation){
        $search = new Search();
        $origin =false;

        $clientId = $fieldValidation["clientId"];

        $clientIdString = 'cliente_id';

        $search->addQuery(new MatchQuery($clientIdString, $clientId/* 100631 */), BoolQuery::FILTER);
        $search->addQuery(new MatchQuery('estado', 1), BoolQuery::FILTER);
        //pagination
        if (isset($fieldValidation["pagination"])) {
            $pagination = $fieldValidation["pagination"];
        }

        $page = isset($pagination->page) ? $pagination->page : 1;
        $pageSize = isset($pagination->limit) ? $pagination->limit : 50;
        
        $search->setSize($pageSize);
        $search->setFrom($page - 1);
        if ($search->getFrom() > 0) {
            $search->setFrom(($search->getFrom() * $pageSize));
        }

        //filtros
        if (isset($fieldValidation["filter"]->id)) $search->addQuery(new MatchQuery('id', $fieldValidation["filter"]->id), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->title)) $search->addQuery(new WildcardQuery('titulo', '*' . $fieldValidation["filter"]->title . '*'));
        if (isset($fieldValidation["filter"]->invoiceNumber)) $search->addQuery(new MatchQuery('numerofactura', $fieldValidation["filter"]->invoiceNumber), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->description)) $search->addQuery(new MatchQuery('descripcion', $fieldValidation["filter"]->description), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->categorieId)) $search->addQuery(new MatchQuery('categorias', $fieldValidation["filter"]->categorieId), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->amount)) $search->addQuery(new MatchQuery('valor', $fieldValidation["filter"]->amount), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->currency)) $search->addQuery(new MatchQuery('moneda', $fieldValidation["filter"]->currency), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->tax)) $search->addQuery(new MatchQuery('iva', $fieldValidation["filter"]->tax), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->baseTax)) $search->addQuery(new MatchQuery('base_iva', $fieldValidation["filter"]->baseTax), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->discountPrice)) $search->addQuery(new MatchQuery('precio_descuento', $fieldValidation["filter"]->discountPrice), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->onePayment)) $search->addQuery(new MatchQuery('cobrounico', $fieldValidation["filter"]->onePayment), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->quantity)) $search->addQuery(new MatchQuery('cantidad', $fieldValidation["filter"]->quantity), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->available)) $search->addQuery(new MatchQuery('disponible', $fieldValidation["filter"]->available), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->expirationDate)) $search->addQuery(new MatchQuery('fecha_expiracion', $fieldValidation["filter"]->expirationDate), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->urlResponse)) $search->addQuery(new MatchQuery('url_respuesta', $fieldValidation["filter"]->urlResponse), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->urlConfirmation)) $search->addQuery(new MatchQuery('url_confirmacion', $fieldValidation["filter"]->urlConfirmation), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->catalogueId)) $search->addQuery(new MatchQuery('catalogo_id', $fieldValidation["filter"]->catalogueId), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->contactName)) $search->addQuery(new MatchQuery('nombre_contacto', $fieldValidation["filter"]->contactName), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->contactNumber)) $search->addQuery(new MatchQuery('numero_contacto', $fieldValidation["filter"]->contactNumber), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->sales)) $search->addQuery(new MatchQuery('ventas', $fieldValidation["filter"]->sales), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->outstanding)) $search->addQuery(new MatchQuery('destacado', $fieldValidation["filter"]->outstanding), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->onlyActive) && $fieldValidation["filter"]->onlyActive) $search->addQuery(new MatchQuery('activo', $fieldValidation["filter"]->onlyActive), BoolQuery::FILTER);

        if ( $this->validateOriginAndAlliedEntity($clientId,$fieldValidation)){
            $origin=true;
            $search->addQuery(new MatchQuery('origen', 'epayco'), BoolQuery::FILTER);
            $this->setEpaycoOrderList($search,$fieldValidation);
        }else{
            $search->addSort(new FieldSort('titulo.keyword', 'ASC'));
        }
        $query = $search->toArray();
        $this->setEpaycoPriceOrderList($query,$fieldValidation);
        $invoices = $this->consultElasticSearch($query, "producto", false);
        return array($invoices,$page,$pageSize, $clientId,$clientIdString,$origin);
    }

    private function validateOriginAndAlliedEntity($clientId,$fieldValidation){
        $apifyClient = $this->getAlliedEntity($clientId);

        $originByAlliedEntity = null;

        $vendeConfig = new VendeConfigPlanService();
        $vendeConfig->setOriginByAlliedEntity($apifyClient,$originByAlliedEntity);

        return (isset($fieldValidation["filter"]->origin) && $fieldValidation["filter"]->origin == CommonText::ORIGIN_EPAYCO) ||
            $originByAlliedEntity==CommonText::ORIGIN_EPAYCO;

    }

    private function setEpaycoPriceOrderList(&$query,$fieldValidation){

        $order = isset($fieldValidation["filter"]->order) ? $fieldValidation["filter"]->order:"";

        if(isset($fieldValidation["filter"]->origin) && $fieldValidation["filter"]->origin == 'epayco' && (strpos($order, 'amount') !== false)){

            $sortType = $order=="amount_desc"?"desc":"asc";

            $query["sort"] = [
                "_script" =>
                    [
                        "type" => "number",
                        "script" =>
                            [
                                "lang" => "painless",
                                "source" => 'doc[\'precio_descuento\'].value == 0 ? doc[\'valor\'].value : doc[\'precio_descuento\'].value',
                            ],
                        "order" => $sortType,
                    ],
            ];
        }
    }

    private function setEpaycoOrderList(&$search,$fieldValidation){

        $order = isset($fieldValidation["filter"]->order) ? $fieldValidation["filter"]->order:"";
        if($order == "date_asc"){
            $search->addSort(new FieldSort('fecha', 'ASC'));
        }else if($order == "outstanding"){
            $search->addSort(new FieldSort('destacado', 'DESC'));
        }else{
            $search->addSort(new FieldSort('fecha', 'DESC'));
        }

    }

    private function getCatalogueName($invoices,$catalogs){
        $catalogueName = "";

        foreach ($invoices['data'] as $value) {
            foreach ($catalogs as $catalogue) {
                if (isset($value->catalogo_id) && $catalogue->id == $value->catalogo_id) {
                    $catalogueName = $catalogue->nombre;
                    break;
                }
            }
        }

        return $catalogueName;
    }

    public function setLastMonthSales(&$invoices){
        $products = $invoices["data"];
        $searchProductsInShoppingCarts = new Search();
        $searchProductsInShoppingCarts->setSize(5000);
        $searchProductsInShoppingCarts->setFrom(0);

        $boolQuery = new BoolQuery();

        $rangeQuery = new RangeQuery('fecha', [
            "gte" => date('Y-m-01'),
            "lte" => date('c')
        ]);

        $boolQuery->add($rangeQuery, BoolQuery::FILTER);
        $boolQuery->add(new MatchQuery('estado', 'pagado'), BoolQuery::FILTER);

        //preparar nested query
        $boolNestedQuery = new BoolQuery();

        foreach ($products as $product) {
            $boolNestedQuery->add(new TermQuery("productos.id", $product->id), BoolQuery::SHOULD);
        }

        $nestedQuery = new NestedQuery(
            'productos',
            $boolNestedQuery
        );

        $boolQuery->add($nestedQuery,BoolQuery::MUST);
        // fin preparar nested query

        $searchProductsInShoppingCarts->addQuery($boolQuery);
        $searchProductsInShoppingCartsResult = $this->consultElasticSearch($searchProductsInShoppingCarts->toArray(), "shoppingcart", false);
        
        $shoppingcartsWithProducts = $searchProductsInShoppingCartsResult["data"];

        $productsId = array_column($products,'id');
        foreach ($shoppingcartsWithProducts as $shoppingcart){
            foreach($shoppingcart->productos as $shoppingcartProduct){
                $matchIndex = array_search($shoppingcartProduct->id,$productsId);
                if($matchIndex>=0){
                    $this->addProductUnitSales($invoices,$shoppingcartProduct,$matchIndex);
                }
            }
        }
    }

    public function addProductUnitSales(&$invoices,$shoppingcartProduct,$invoiceMathIndex){

        $unitSales = 0;

        if(isset($shoppingcartProduct->referencias)){
            foreach($shoppingcartProduct->referencias as $reference){
                $unitSales = $unitSales + $reference->cantidad;
            }
        }else{
            $unitSales = $shoppingcartProduct->cantidad;
        }

        if(isset($invoices["data"][$invoiceMathIndex]->ventas_ultimo_mes)){
            $invoices["data"][$invoiceMathIndex]->ventas_ultimo_mes += $unitSales;
        }else{
            $invoices["data"][$invoiceMathIndex]->ventas_ultimo_mes = $unitSales;
        }
    }

    public function setRelatedProducts(&$data,$key,$filterId,$value){

        if($filterId>0 && isset($value->categorias[0])){
            $category = $value->categorias[0];

            $timeArray = explode(" ", microtime());
            $timeArray[0] = str_replace('.', '', $timeArray[0]);
            $randomSeed = (int) ($timeArray[1] . substr($timeArray[0], 2, 3));

            $searchRelatedProducts = new Search();
            $searchRelatedProducts->setSize(5);
            $searchRelatedProducts->setFrom(0);
            $bool = new BoolQuery();
            $bool->add(new TermQuery("estado", 1),BoolQuery::MUST);
            $bool->add(new TermQuery("origen", "epayco"),BoolQuery::MUST);
            $bool->add(new TermQuery("categorias", $category),BoolQuery::MUST);
            $bool->add(new TermQuery("id", $value->id),BoolQuery::MUST_NOT);
            $functionScoreQuery = new FunctionScoreQuery($bool);
            $functionScoreQuery->addRandomFunction($randomSeed);
            $searchRelatedProducts->addQuery($functionScoreQuery);

            $relatedProductsResults = $this->consultElasticSearch($searchRelatedProducts->toArray(), "producto", false);
            $relatedProductsArray = [];

            if(isset($relatedProductsResults["data"])){
                $relatedProductsArray = $this->setRelatedProductsHelper($relatedProductsResults,$value);
            }
            $data[$key]["relatedProducts"] = $relatedProductsArray;

        }
    }

    private function setRelatedProductsHelper($relatedProductsResults,$value) {
        $relatedProductsArray = [];
        foreach($relatedProductsResults["data"] as $relatedProduct){

            $images = [];

            foreach($relatedProduct->img as $img){
                array_push($images,getenv("AWS_BASE_PUBLIC_URL") . '/' . $img);
            }

            array_push($relatedProductsArray,[
                "id"=>$relatedProduct->id,
                "discountPrice"=>$this->getFieldValidation((array)$relatedProduct,"precio_descuento",0),
                "discountRate"=>$this->getFieldValidation((array)$relatedProduct,"porcentaje_descuento",0),
                "netAmount"=>$this->getFieldValidation((array)$relatedProduct,"monto_neto",$relatedProduct->valor),
                "epaycoDeliveryProvider" => $this->getFieldValidation((array)$value,CommonText::EPAYCO_LOGISTIC,false),
                "epaycoDeliveryProviderValues" => $this->getFieldValidation((array)$value,CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES,[]),
                "realWeight" => $this->getFieldValidation((array)$value,CommonText::REAL_WEIGHT,0),
                "high" => $this->getFieldValidation((array)$value,CommonText::HIGH,0),
                "long" => $this->getFieldValidation((array)$value,CommonText::LONG,0),
                "width" => $this->getFieldValidation((array)$value,CommonText::WIDTH,0),
                "declaredValue" => $this->getFieldValidation((array)$value,CommonText::DECLARED_VALUE,0),
                "title"=>$relatedProduct->titulo,
                "references"=>isset($relatedProduct->referencias) ? $this->mappingRelatedProductReference($relatedProduct->referencias) : [],
                "setupReferences"=>isset($relatedProduct->configuraciones_referencias) ? $this->mappingRelatedSetupReference($relatedProduct->configuraciones_referencias) : [],
                "amount"=>$relatedProduct->valor,
                "img"=>$images
            ]);
        }
        return $relatedProductsArray;
    }

    private function mappingRelatedSetupReference($setupReferences){
        $setup = [];
        foreach ($setupReferences as $reference) {
            array_push($setup,[
                "name"=>$this->getFieldValidation((array)$reference,"nombre",""),
                "type"=>$this->getFieldValidation((array)$reference,"tipo",""),
                "values"=>$this->getFieldValidation((array)$reference,"valores",[])
            ]);
        }
        return $setup;
    }

    private function mappingRelatedProductReference($referencesData){
        $relatedProducts = [];
        foreach ($referencesData as $reference) {
            array_push($relatedProducts,[
                "id"=>$reference->id,
                "discountPrice"=>$this->getFieldValidation((array)$reference,"precio_descuento",0),
                "discountRate"=>$this->getFieldValidation((array)$reference,"porcentaje_descuento",0),
                "netAmount"=>$this->getFieldValidation((array)$reference,"monto_neto",$reference->valor),
                "title"=>$reference->nombre,
                "name"=>$reference->nombre,
                "amount"=>$reference->valor,
                "img"=>$reference->img
            ]);
        }
        return $relatedProducts;
    }

    private function setCategoryNameAndStatus(&$categoryName,&$statusCategory,$catalogue,$value){

        if(isset($value->categorias) && !empty($value->categorias)){
            $categoryId = $value->categorias[0];
            $categories = $catalogue->categorias;
            $targetCategoryIndex = array_search($categoryId, array_column((array)$categories, 'id'));
            $targetCategory = $categories[$targetCategoryIndex];
            $categoryName = $targetCategory->nombre;
            if($targetCategory->id == 1 || (isset($targetCategory->activo) && !$targetCategory->activo)){
                $statusCategory = "Inactivo";
            }
        }

    }
    public function listData($invoices,$clientSubdomain,$catalogs,$clientId,$routeQrString,$routeLinkString,$origin,$filterId){

        $data = [];
        $this->setLastMonthSales($invoices);

        foreach ($invoices['data'] as $key => $value) {

            $catalogueName = "";
            $categoryName = "";
            $statusCategory = "Activo";
            foreach ($catalogs as $catalogue) {
                if (isset($value->catalogo_id) && $catalogue->id == $value->catalogo_id) {
                    $catalogueName = $catalogue->nombre;
                    $this->setCategoryNameAndStatus($categoryName,$statusCategory,$catalogue,$value);
                    break;
                }
            }

            $landingUrl = $this->getPathByOrigin($origin,$catalogueName,$clientSubdomain);


            $amount = $value->valor;
            $netAmount = $this->getFieldValidation((array)$value,'monto_neto',$value->valor);
            $discountPrice = $this->getFieldValidation((array)$value,'precio_descuento',$value->valor);
            $discountRate = $this->getFieldValidation((array)$value,'porcentaje_descuento',$value->valor);
            if($origin){
                $salePrice = $value->porcentaje_descuento > 0 ? $value->precio_descuento : $value->valor;
                if (isset($value->referencias) &&  count($value->referencias) > 0) {
                    $salePrice = $value->referencias[0]->porcentaje_descuento > 0 ? $value->referencias[0]->precio_descuento : $value->referencias[0]->valor;
                    $amount = $value->referencias[0]->valor;
                    $netAmount = $value->referencias[0]->monto_neto;
                    $discountPrice = $value->referencias[0]->precio_descuento;
                    $discountRate = $value->referencias[0]->porcentaje_descuento;
                }
                $data[$key]['discountRate'] = $discountRate;
                $data[$key]['updateDate'] = $this->getFieldValidation((array)$value,'fecha_actualizacion',$value->fecha);
                $data[$key]['showInventory'] = $this->getFieldValidation((array)$value,'mostrar_inventario',false);
                $data[$key]['outstanding'] = $value->destacado;
                $data[$key]['discountPrice'] = $discountPrice;
                $data[$key]['origin'] = $value->origen;
                $data[$key]['catalogueName'] = $catalogueName;
                $data[$key]['catalogueId'] = $value->catalogo_id;
                $data[$key]['categoryName'] = $categoryName;
                $data[$key]['statusCategory'] = $statusCategory;
                $data[$key]['active'] = !isset($value->activo) ? true : $value->activo;
                $data[$key]['statusProduct'] = $this->getProductStatus($data[$key],$value->edata_estado);
                $data[$key]['sales'] = $value->ventas;
                $data[$key]['activeTax'] = $this->getFieldValidation((array)$value,'iva_activo',false);
                $data[$key]['activeConsumptionTax'] = $this->getFieldValidation((array)$value,'ipoconsumo_activo',false);
                $data[$key]['consumptionTax'] = $this->getFieldValidation((array)$value,'ipoconsumo',0);
                $data[$key]['netAmount'] = $netAmount;
                $data[$key]['salePrice'] = $salePrice;
                $data[$key]['epaycoDeliveryProvider'] = $this->getFieldValidation((array)$value,CommonText::EPAYCO_LOGISTIC,false);
                $data[$key]['epaycoDeliveryProviderValues'] = $this->getFieldValidation((array)$value,CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES,[]);
                $data[$key]['realWeight'] = $this->getFieldValidation((array)$value,CommonText::REAL_WEIGHT,0);
                $data[$key]['high'] = $this->getFieldValidation((array)$value,CommonText::HIGH,0);
                $data[$key]['long'] = $this->getFieldValidation((array)$value,CommonText::LONG,0);
                $data[$key]['width'] = $this->getFieldValidation((array)$value,CommonText::WIDTH,0);
                $data[$key]['declaredValue'] = $this->getFieldValidation((array)$value,CommonText::DECLARED_VALUE,0);
                $data[$key]['setupReferences'] = isset($value->configuraciones_referencias) ? $this->mappingRelatedSetupReference($value->configuraciones_referencias) : [];
                $this->setRelatedProducts($data,$key,$filterId,$value);
            }

            $data[$key]['date'] = $value->fecha;
            $data[$key]['state'] = $value->estado;
            $data[$key]['txtCode'] = $value->id;
            $data[$key]['clientId'] = $clientId;
            $data[$key]['quantity'] = $value->cantidad;
            $data[$key]['baseTax'] = $value->base_iva;
            $data[$key]['description'] = $value->descripcion;
            $data[$key]['title'] = $value->titulo;
            $data[$key]['currency'] = $value->moneda;
            $data[$key]['urlConfirmation'] = $value->url_confirmacion;
            $data[$key]['urlResponse'] = $value->url_respuesta;
            $data[$key]['tax'] = $value->iva;
            $data[$key]['amount'] = $amount;
            $data[$key]['invoiceNumber'] = $value->numerofactura;
            $data[$key]['expirationDate'] = $value->fecha_expiracion;
            $data[$key]['contactName'] = $value->nombre_contacto;
            $data[$key]['contactNumber'] = $value->numero_contacto;
            $data[$key][$routeQrString] = "http://secure2.epayco.io/apprest/printqr?txtcodigo=" . $landingUrl . $value->id;
            $data[$key][$routeLinkString] = $landingUrl . $value->id;
            $data[$key]['id'] = $value->id;
            $data[$key]['lastMonthSales'] = isset($value->ventas_ultimo_mes)?$value->ventas_ultimo_mes:0;
            $data[$key]['edataStatus'] = isset($value->edata_estado) ? $value->edata_estado : HelperEdata::STATUS_ALLOW;

            if (isset($value->img) ) {
                $data[$key]['img'] = [];
                foreach ($value->img as $ki => $img) {
                    if(!empty($img)){
                        $data[$key]['img'][$ki] = getenv("AWS_BASE_PUBLIC_URL") . '/' . $img;
                    }
                }
            } else {
                $data[$key]['img'] = [];
            }

            if($origin){
                $data[$key]['firsImage'] = isset($data[$key]['img'][0]) ? $data[$key]['img'][0] : "";
            }


            $data[$key]['shippingTypes'] = [];

            if (isset($value->envio) && count($value->envio) > 0) {
                foreach ($value->envio as $kv => $env) {
                    $data[$key]['shippingTypes'][$kv]['type'] = $env->tipo;
                    $data[$key]['shippingTypes'][$kv]['amount'] = $env->valor;
                }
            }

            if (isset($value->categorias) && count($value->categorias) > 0) {
                foreach ($value->categorias as $kc => $cat) {
                    $data[$key]['categories'][$kc] = $cat;
                }
            }
            $data[$key]['references'] = [];
            if (isset($value->referencias) &&  count($value->referencias) > 0) {
                if ($value->referencias[0]->id != null) {
                    $available = 0;
                    $refeences = is_array($value->referencias) ? $value->referencias : (array)$value->referencias;
                    foreach ($refeences as $kref => $ref) {
                        $data[$key]['references'][$kref]['description'] = isset($ref->descripcion) ? $ref->descripcion : '';
                        $data[$key]['references'][$kref]['invoiceNumber'] = isset($ref->numerofactura) ? $ref->numerofactura : '';
                        $data[$key]['references'][$kref]['urlResponse'] = isset($ref->url_respuesta) ? $ref->url_respuesta : '';
                        $data[$key]['references'][$kref]['amount'] = isset($ref->valor) ? $ref->valor : 0;
                        $data[$key]['references'][$kref]['expirationDate'] = isset($ref->fecha_expiracion) ? $ref->fecha_expiracion : '';
                        $data[$key]['references'][$kref]['title'] = isset($ref->nombre) ? $ref->nombre : '';
                        $data[$key]['references'][$kref]['baseTax'] = isset($ref->base_iva) ? $ref->base_iva : 0;
                        $data[$key]['references'][$kref]['date'] = isset($ref->fecha) ? $ref->fecha : '';
                        $data[$key]['references'][$kref]['urlConfirmation'] = isset($ref->url_confirmacion) ? $ref->url_confirmacion : '';
                        $data[$key]['references'][$kref][$routeLinkString] = $data[$key][$routeLinkString];
                        $data[$key]['references'][$kref][$routeQrString] = $data[$key][$routeQrString];
                        $data[$key]['references'][$kref]['txtCode'] = isset($ref->txtcodigo) ? $ref->txtcodigo : '';
                        $data[$key]['references'][$kref]['tax'] = isset($ref->iva) ? $ref->iva : 0;
                        $data[$key]['references'][$kref]['currency'] = isset($ref->moneda) ? $ref->moneda : '';
                        $data[$key]['references'][$kref]['quantity'] = isset($ref->cantidad) ? $ref->cantidad : 0;
                        $data[$key]['references'][$kref]['id'] = isset($ref->id) ? $ref->id : '';
                        $data[$key]['references'][$kref]['available'] = isset($ref->disponible) ? $ref->disponible : 0;
                        if($origin){
                            $data[$key]['references'][$kref]['name'] = isset($ref->nombre) ? $ref->nombre : '';
                            $data[$key]['references'][$kref]['discountRate'] = isset($ref->porcentaje_descuento) ? $ref->porcentaje_descuento : 0;
                            $data[$key]['references'][$kref]['discountPrice'] = isset($ref->precio_descuento) ? $ref->precio_descuento : 0;
                            $data[$key]['references'][$kref]['netAmount'] = isset($ref->monto_neto) ? $ref->monto_neto : 0;
                            $data[$key]['references'][$kref]['consumptionTax'] = isset($ref->ipoconsumo) ? $ref->ipoconsumo : 0;
                        }
                        $available = $available + $ref->disponible;
                        if (isset($ref->img) && is_array(($ref->img))) {
                            $referencesImg = [];
                            foreach ($ref->img as $referenceImg) {
                                array_push($referencesImg,getenv("AWS_BASE_PUBLIC_URL") . '/' . $referenceImg);
                            }
                            $data[$key]['references'][$kref]['img'] = $referencesImg;
                        }else if (isset($ref->img)) {
                            $data[$key]['references'][$kref]['img'] = $ref->img !== '' && $ref->img !== null ? getenv("AWS_BASE_PUBLIC_URL") . '/' .$ref->img : null;
                        } else {
                            $data[$key]['references'][$kref]['img'] = [];
                        }
                        $data[$key]['available'] = $available;
                    }
                }else {
                    $data[$key]['available'] = $value->disponible;
                }
            } else {
                $data[$key]['available'] = $value->disponible;
            }
        }

        return array($data);
    }

    private function getFieldValidation($fields,$name,$default = ""){

        return isset($fields[$name]) ? $fields[$name] : $default;

    }

    private function getProductStatus($product,$edataStatus){
        $status = $product['active'] === true ? "Activo" : "Inactivo";

        if($edataStatus == HelperEdata::STATUS_ALERT){
            $status = HelperEdata::STATUS_ALERT;
        }

        return $status;
    }
}
