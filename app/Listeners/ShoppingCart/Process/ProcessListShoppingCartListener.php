<?php

namespace App\Listeners\ShoppingCart\Process;

use App\Events\ShoppingCart\Process\ProcessListShoppingCartEvent;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\FiltersAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use \Illuminate\Http\Request;

class ProcessListShoppingCartListener extends HelperPago
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

    public function handle(ProcessListShoppingCartEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $pagination = $fieldValidation["pagination"];
            $filters = $fieldValidation["filter"];
            $id = isset($filters->id) ? $filters->id : "";
            $origin = isset($filters->origin) ? $filters->origin : "";
            $state = isset($filters->state) ? $filters->state : "";
            $initialDate = isset($filters->initialDate) ? $filters->initialDate : "";
            $endDate = isset($filters->endDate) ? $filters->endDate : "";
            $minAmount = isset($filters->minAmount) ? $filters->minAmount : "";
            $maxAmount = isset($filters->maxAmount) ? $filters->maxAmount : "";
            $aggregation = isset($filters->aggregation) ? $filters->aggregation : false;
            $filter = isset($filters->filter) ? $filters->filter : null;

            $page = isset($pagination->page) ? $pagination->page : 1;
            $limit = isset($pagination->limit) ? $pagination->limit : 50;
            $aggregations = [];

            $searchShoppingCart = new Search();
            $searchShoppingCart->setSize($limit);
            $searchShoppingCart->setFrom($page - 1);

            if ($searchShoppingCart->getFrom() > 0) {
                $searchShoppingCart->setFrom(($searchShoppingCart->getFrom() * $limit));
            }
            $searchShoppingCart->addSort(new FieldSort('fecha', 'DESC'));

            $searchShoppingCart->addQuery(new MatchQuery('clienteId', $clientId), BoolQuery::FILTER);
            if ($id != "") {
                $searchShoppingCart->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
            }

            if ($state != "") {
                if ($state !== "rechazada") {
                    $searchShoppingCart->addQuery(new MatchQuery('estado.keyword', $state), BoolQuery::FILTER);
                } else {
                    $searchShoppingCart->addQuery(new MatchQuery('ultimo_estado_pago', $state), BoolQuery::FILTER);
                }
            }

            if ($initialDate != "" || $endDate != "") {
                $rangeDate = [];
                if ($initialDate != "") {
                    $rangeDate["gte"] = $initialDate;
                }

                if ($endDate != "") {
                    $rangeDate["lte"] = $endDate;
                }

                $rangeDateQuery = new RangeQuery('fecha', $rangeDate);
                $searchShoppingCart->addQuery($rangeDateQuery, BoolQuery::FILTER);
            }

            if ($minAmount != "" || $maxAmount != "") {
                $rangeAmout = [];
                if ($minAmount != "") {
                    $rangeAmout["gte"] = $minAmount;
                }

                if ($maxAmount != "") {
                    $rangeAmout["lte"] = $maxAmount;
                }

                $rangeAmountQuery = new RangeQuery('total', $rangeAmout);
                $searchShoppingCart->addQuery($rangeAmountQuery, BoolQuery::FILTER);
            }

            $this->getAggregationsShoppingCart($searchShoppingCart, $clientId, $origin, $aggregation);
            $this->addfiltersGetShoppingCart($searchShoppingCart, $filter);
            $shoppingCartResult = $this->consultElasticSearch($searchShoppingCart->toArray(), "shoppingcart", false);

            if ($shoppingCartResult["status"]) {

                $shoppingCarts = [];

                foreach ($shoppingCartResult["data"] as $shoppingCartData) {

                    $shoppingCartResponseData = [
                        "id" => $shoppingCartData->id,
                        "total" => $shoppingCartData->total,
                        "quantity" => $this->parseQuantity($shoppingCartData),
                        "state" => $shoppingCartData->estado,
                        "date" => $shoppingCartData->fecha,
                        "clientId" => $clientId,
                    ];
                    $this->setResponseDataShoppingCart($shoppingCartResponseData, $origin, $shoppingCartData);
                    array_push($shoppingCarts, $shoppingCartResponseData);
                }

                $success = true;
                $title_response = 'List Shopping cart';
                $text_response = 'List Shopping cart';
                $last_action = 'shopping_cart';
                $data = $shoppingCarts;
                if ($aggregation) {
                    $aggregations = $shoppingCartResult["aggregations"];
                }
            } else {
                $success = false;
                $title_response = 'Unsuccessfully consult shopping cart';
                $text_response = 'Unsuccessfully consult shopping cart';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }

        } catch (\Exception$exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error get shopping cart";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }

        $paginate = [
            "current_page" => $page,
            "data" => [],
            "from" => $page <= 1 ? 1 : ($page * $limit) - ($limit - 1),
            "last_page" => ceil($shoppingCartResult['pagination']['totalCount'] / $limit),
            "next_page_url" => "/catalogue/shoppingcart/list?page=" . ($page + 1),
            "per_page" => $limit,
            "prev_page_url" => $page <= 2 ? null : "/catalogue/shoppingcart/list?pague=" . ($page - 1),
            "to" => $page <= 1 ? count($data) : ($page * $limit) - ($limit - 1) + (count($data) - 1),
            "total" => $shoppingCartResult['pagination']['totalCount'],
        ];

        $this->setResponseData($data, $origin, $paginate, $aggregations, $aggregation);

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

        foreach ($car->productos as $product) {
            if (isset($product->referencias)) {
                foreach ($product->referencias as $key => $value) {
                    $quantity += $value->cantidad;
                }
            } else {
                $quantity += $product->cantidad;
            }
        }

        return $quantity;
    }

    public function setResponseData(&$data, $origin, $paginate, $aggregations, $aggregation)
    {
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $reponse = $paginate;
            $reponse["data"] = $data;
            if ($aggregation) {
                $reponse["aggregations"] = $aggregations;
            }
            $data = $reponse;
        }
    }

    public function setResponseDataShoppingCart(&$shoppingCartData, $origin, $data)
    {
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $shoppingCartData['statePay'] = isset($data->ultimo_estado_pago) ? $data->ultimo_estado_pago : CommonText::DEFAULT_LAST_TRANSACTION_SHOPPINGCART_STATUS;
            $shoppingCartData['stateDelivery'] = isset($data->estado_entrega) ? $data->estado_entrega : CommonText::DEFAULT_SHOPPINGCART_STATUS_DELIVERY;
        }
    }

    public function getAggregationsShoppingCart(&$search, $clientId, $origin, $aggregation)
    {
        if ($origin == CommonText::ORIGIN_EPAYCO && $aggregation) {

            $entregas = [
                "Pendiente" => "No aplica",
                "Programado" => "envio_programado",
                "Entregado" => "entregado",
            ];

            $pagos = [
                "Rechazado" => "Rechazada",
                "Pendiente" => "No aplica",
                "Aprobado" => "Aceptada",
            ];

            $carritos = [
                "Activo" => "activo",
                "Abandonado" => "abandonado",
                "Eliminado" => "eliminado",
                "Procesando_pago" => "procesando_pago",
                "Completado" => "pagado",
            ];

            $catalogueQuery = new Search();

            //Contador estado de entrega
            $aggregationEntrega = new FiltersAggregation('Entrega');
            foreach ($entregas as $k => $entrega) {
                $aggregationEntrega->addFilter(new MatchQuery('estado_entrega', $entrega), $k);
            }
            //Contador estado del pago
            $aggregationPago = new FiltersAggregation('Pago');
            foreach ($pagos as $k => $pago) {
                $aggregationPago->addFilter(new MatchQuery('ultimo_estado_pago', $pago), $k);
            }
            //Contador de estado del carrito
            $aggregationsCarritos = new FiltersAggregation('Carrito');
            foreach ($carritos as $k => $carrito) {
                $aggregationsCarritos->addFilter(new MatchQuery('estado', $carrito), $k);
            }

            //consultar catalogos
            $catalogueQuery->setFrom(0);
            $catalogueQuery->addQuery(new MatchQuery('estado', true), BoolQuery::FILTER);
            $catalogueQuery->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
            $catalogueResult = $this->consultElasticSearch($catalogueQuery->toArray(), "catalogo", false);
            if (!empty($catalogueResult["data"])) {
                $catalogos = [];
                foreach ($catalogueResult["data"] as $catalogue) {
                    $catalogos[$catalogue->nombre] = $catalogue->id;
                }
                $aggregationsCatalogos = new FiltersAggregation('Catalogos');
                foreach ($catalogos as $k => $catalogo) {
                    $aggregationsCatalogos->addFilter(new MatchQuery('catalogo_id', $catalogo), $k);
                }
                $search->addAggregation($aggregationsCatalogos);
            }

            //Total data
            $aggregationTotal = new FiltersAggregation("Total");
            $aggregationTotal->addFilter(new TermQuery("clienteId", $clientId), "total");

            $search->addAggregation($aggregationEntrega);
            $search->addAggregation($aggregationPago);
            $search->addAggregation($aggregationsCarritos);
            $search->addAggregation($aggregationTotal);
        }
    }

    public function addfiltersGetShoppingCart(&$search, $filter)
    {
        if (!empty($filter)) {
            foreach ($filter as $item) {
                if ($item["valor1"] !== "") {
                    $search->addQuery(new MatchQuery($item["campo"], $item["valor1"]), BoolQuery::FILTER);
                }
            }
        }
    }
}
