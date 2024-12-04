<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ShoppingCartRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ListShoppingCartService extends HelperPago
{

    private $shopping_cart_repository;
    private $catalogueRepository;

    public function __construct(Request $request, ShoppingCartRepository $shopping_cart_repository, CatalogueRepository $catalogueRepository) {
        parent::__construct($request);
        $this->shopping_cart_repository = $shopping_cart_repository;
        $this->catalogueRepository=$catalogueRepository;
    }

    public function handle($params)
    {
        try {
            
            $fieldValidation = $params;
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


            $shoppingCartResult = $this->shopping_cart_repository->getShoppingCartWithFilters($id,$state,$initialDate,$endDate,$maxAmount,$minAmount,$filter,$limit,$clientId, $origin, $aggregation,$page);
            $dataAggregations = $this->shopping_cart_repository->getShoppingCartWithAggregations($id, $state, $initialDate, $endDate, $maxAmount, $minAmount, $filter, $limit, $clientId, $origin, $aggregation);
            $dataAggregations =  $this->formatAgregations($dataAggregations);
            if (!empty($shoppingCartResult)) {

                $shoppingCarts = [];

                foreach ($shoppingCartResult as $shoppingCartData) {

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
                    $aggregations = $dataAggregations;
                }
            } else {
                $success = false;
                $title_response = 'Unsuccessfully consult shopping cart';
                $text_response = 'Unsuccessfully consult shopping cart';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }

        } catch (\Exception $exception) {
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

        $paginate = [
            "current_page" => $page,
            "data" => [],
            "from" => $page <= 1 ? 1 : ($page * $limit) - ($limit - 1),
            "last_page" => $shoppingCartResult->lastPage(),
            "next_page_url" => "/catalogue/shoppingcart/list?page=" . ($page + 1),
            "per_page" => $limit,
            "prev_page_url" => $page <= 2 ? null : "/catalogue/shoppingcart/list?pague=" . ($page - 1),
            "to" => $page <= 1 ? count($data) : ($page * $limit) - ($limit - 1) + (count($data) - 1),
            "total" => $shoppingCartResult->total(),
        ];

        $this->setResponseData($data, $origin, $paginate, $aggregations, $aggregation);
        $data['catalogueOptions']=$this->formatCatalogueOptions($clientId);
        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
    private function formatCatalogueOptions($clientId)
    {
        $options = [];
        $catalogues = $this->catalogueRepository->findByClientIdActive($clientId);
        foreach ($catalogues as $catalogue) {
            array_push($options, [
                "name" => $catalogue->nombre,
                "id" => $catalogue->id,
            ]);
        }
    
        return $options;
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
    private function formatAgregations($dataAggregations)
    {
        $newDataAggregations = [];
        foreach ($dataAggregations as $name => $agg) {
            $newDataAggregations[$name]['buckets'] = $this->formatBaggregations($agg);
        }
        return $newDataAggregations;
    }
    private function formatBaggregations($agg)
    {
        $newDataAggregationsB = [];
        foreach ($agg as $name => $value) {
            $newDataAggregationsB[$name]['doc_count'] = $value;
        }
        return $newDataAggregationsB;
    }
    //PARSE PRODUCT QUANTITY
    public function parseQuantity($car)
    {
        $quantity = 0;
    
        foreach ($car->productos as $product) {

           
            if (isset($product["referencias"]) && count($product["referencias"])>1) {

                foreach ($product["referencias"] as $key => $value) {
                    $quantity += $value["cantidad"];
                }
            } else {
                $quantity += $product["cantidad"];
            }
        }

        return $quantity;
    }

    public function setResponseDataShoppingCart(&$shoppingCartData, $origin, $data)
    {
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $shoppingCartData['statePay'] = isset($data["ultimo_estado_pago"]) ? $data["ultimo_estado_pago"] : CommonText::DEFAULT_LAST_TRANSACTION_SHOPPINGCART_STATUS;
            $shoppingCartData['stateDelivery'] = isset($data["estado_entrega"]) ? $data["estado_entrega"] : CommonText::DEFAULT_SHOPPINGCART_STATUS_DELIVERY;
        }
    }

}
