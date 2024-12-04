<?php


namespace App\Listeners\Services;

use stdClass;
use App\Common\ProductClientStateCodes;
use App\Exceptions\GeneralException;
use App\Helpers\Edata\HelperEdata;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Models\DetalleConfClientes;
use App\Models\ApifyClientes;
use App\Models\Productos;
use App\Models\ProductosClientes;
use Exception;
use Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Compound\FunctionScoreQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\BuilderInterface;
use App\Helpers\Validation\CommonValidation;
use App\Helpers\Messages\CommonText as CM;

class MercadoePaycoService extends HelperPago
{
  public function __construct()
  {
      parent::__construct(new Request());
  }

  public function getAllProductsOfMercadoePayco($catalogs, $arr_parametros) {
    $page = CommonValidation::validateIsSet($arr_parametros, 'page', 1, 'int');
    $pageSize = CommonValidation::validateIsSet($arr_parametros, 'pageSize', 500, 'int');
    $search = new Search();
    $search->addQuery(new MatchQuery(CommonText::STATE, 1), BoolQuery::FILTER);
    $search->addQuery(new MatchQuery(CommonText::ACTIVE, true), BoolQuery::FILTER);
    if (isset($arr_parametros["filter"]) && isset($arr_parametros["filter"]['titulo'])) {
      $queryStringQuery = new QueryStringQuery('(*'.$arr_parametros["filter"]['titulo'].'*) OR ('.$arr_parametros["filter"]['titulo'].')');
      $queryStringQuery->addParameter('fields', ["titulo"]);
      $search->addQuery($queryStringQuery);
    }
    $boolTargetCatalogsForProducts = new BoolQuery();
    foreach ($catalogs["data"] as  $catalog) {
      $boolTargetCatalogsForProducts->add(new TermQuery("catalogo_id", $catalog->id), BoolQuery::SHOULD);
    }
    $search->addQuery($boolTargetCatalogsForProducts);

    $queryData = [
      "query" => [
        "script_score" => $search->toArray()
      ],
      "from" => $page - 1,
      "size" => $pageSize
    ];
    $queryData["query"]["script_score"]["script"] = ["source" => "randomScore(100)"];

    $productResult = $this->searchRawQueryElastic(["indice" => "producto", "data" => $queryData]);
    if ($productResult['success']) {
      return $this->formatResponseRawQuery($productResult['data'], $pageSize, $page);
    } else {
      return null;
    }
  }

  public function getAllCatalogueOfMercadoePayco($arr_parametros) {
    $page = CommonValidation::validateIsSet($arr_parametros, 'page', 1, 'int');
    $pageSize = CommonValidation::validateIsSet($arr_parametros, 'pageSize', 500, 'int');
    $apifyClientId = CommonValidation::validateIsSet($arr_parametros, 'apifyClientId', null, 'int');

    $search = new Search();
    $search->addQuery(new MatchQuery(CommonText::STATE, true), BoolQuery::FILTER);
    $search->addQuery(new MatchQuery(CommonText::ACTIVE, true), BoolQuery::FILTER);
    $search->addQuery(new MatchQuery("mercado_epayco", true), BoolQuery::FILTER);
    $search->addQuery(new MatchQuery("entidad_aliada", $apifyClientId), BoolQuery::FILTER);
    
    if (isset($arr_parametros["filter"]) && isset($arr_parametros["filter"]['nombre'])) {
      $queryStringQuery = new QueryStringQuery('(*'.$arr_parametros["filter"]['nombre'].'*) OR ('.$arr_parametros["filter"]['nombre'].')');
      $queryStringQuery->addParameter('fields', ["nombre"]);
      $search->addQuery($queryStringQuery);
    }
    
    $queryData = [
      "query" => [
        "script_score" => $search->toArray()
      ],
      "from" => $page - 1,
      "size" => $pageSize
    ];
    $queryData["query"]["script_score"]["script"] = ["source" => "randomScore(100)"];
    $catalogueResult = $this->searchRawQueryElastic(["indice" => "catalogo", "data" => $queryData]);
    if ($catalogueResult['success']) {
      $respose = $this->formatResponseRawQuery($catalogueResult['data'], $pageSize, $page);
      return $this->addFiledCatalogue($respose);
    } else {
      return null;
    }
  }

  public function getAlliedEntity ($clientId) {
    $apifyClient = ApifyClientes::where('cliente_id', $clientId)->first();
    return $apifyClient->apify_cliente_id;
  }

  public function formatResponseRawQuery($body, $size = 10, $page = 1) {
    if (isset($body->count)) {
      return ["status" => true, 'pagination' => null, "data" => $body->count, "message" => "Consulta a elasticsearc exitosa count"];
    }
    if (isset($body->hits->total->value)) {
        $data = [];
        foreach ($body->hits->hits as $value) {
            if (isset($value->inner_hits)) {
                array_push($data, $value);
            } else {
                array_push($data, $value->_source);
            }
        }
        $totalCount = $this->countData($body);
        
        $paginacion = ["totalCount" => $totalCount,
            "limit" => $size, "page" => $page];

        return ["status" => true,
            'pagination' => $paginacion,
            "data" => $data,
            "aggregations" => isset($body->aggregations) ? $body->aggregations : null,
            "message" => "Consulta a elasticsearc exitosa"
        ];
    }
  }

  public function countData($body) {
    $totalCount = 0;
    if (isset($body->aggregations) && isset($body->aggregations->data)) {
      $totalCount = $body->aggregations->data->doc_count;
    } else if (isset($body->aggregations) && isset($body->aggregations->total)) {
        $totalCount = $body->aggregations->total->buckets->total->doc_count;
    } else {
        $totalCount = $body->hits->total->value;
    }
    return $totalCount;
  }

  public function addFiledCatalogue($respose) {
    $data = $respose['data'];
    foreach ($respose['data'] as $key => $value) {
      $catalogue = $data[$key];
      $catalogue->link = $this->getClientSubdomain($value->cliente_id, $value->procede, $value->nombre);
      $data[$key] = $catalogue;
    }
    $respose['data'] = $data;
    return $respose;
  }

  public function getClientSubdomain($clientId, $origin,$nameCatalogue){
    $clientSubdomainSearch =  DetalleConfClientes::where("cliente_id","=",$clientId)
        ->where("config_id","=",39)
        ->first();

    $path = '';
    isset($clientSubdomainSearch->valor) && $path = $clientSubdomainSearch->valor;

    $origin !== 'epayco' ? $path = $path."/catalogo/" : $path = $path."/vende/".$nameCatalogue;

    return str_replace(' ', '%20', $path);
}
}