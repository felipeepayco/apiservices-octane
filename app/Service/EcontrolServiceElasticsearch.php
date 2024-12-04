<?php

namespace App\Service;

use App\Models\MediosPago;
use Elasticsearch\Endpoints\Sql\Query;
use Monolog\Handler\IFTTTHandler;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\FiltersAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Compound\DisMaxQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;

class EcontrolServiceElasticsearch
{
    /**
     * @param array $filters
     * @param int $clientId
     * @return array|\ArrayObject|bool|\Countable|float|int|mixed|string|\Traversable|null
     */
    public function search(array $filters, int $clientId = null) {
        $search = new Search();
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $search->setSize($limit);
        $search->setFrom($page - 1); // Se le resta 1 por que el paginador de elastic la primera pagina comienza por 0.
        if ($search->getFrom() > 0) {
            $search->setFrom(($search->getFrom() * $limit));
        }

        $search->addSort(new FieldSort('id', 'DESC'));

        if ($clientId) {
            $search->addQuery(new TermQuery('id_cliente', $clientId), BoolQuery::FILTER);
        }

        $dateRangeQuery = new RangeQuery('fecha');

        // Date format = Y-m-d
        if (isset($filters['fromDate'])) {
            $dateRangeQuery->addParameter(RangeQuery::GTE, $filters['fromDate']);
        }

        if (isset($filters['toDate'])) {
            $dateRangeQuery->addParameter(RangeQuery::LTE, $filters['toDate']);
        }

        if (count($dateRangeQuery->getParameters())) {
            $search->addQuery($dateRangeQuery, BoolQuery::FILTER);
        }

        //id de transaccion
        if (isset($filters['id'])) {
            $search->addQuery(
                new TermQuery('id', $filters['id']),
                BoolQuery::FILTER
            );
        }
  
        if (isset($filters['registros_estados'])) {
            $bool = new BoolQuery();
            $bool->add(new TermQuery('registro_id_estado_econtrol', 3), BoolQuery::MUST);
            $bool->add(new ExistsQuery('registro_estado_gestion'), BoolQuery::MUST);
            $existQuery = $bool;

            $termQuery = new TermsQuery('registro_id_estado_econtrol', $filters['registros_estados']);

            $disMaxQuery = new DisMaxQuery();
            $disMaxQuery->addQuery($existQuery,BoolQuery::MUST);
            $disMaxQuery->addQuery($termQuery, BoolQuery::FILTER);

            $search->addQuery($disMaxQuery);            
        }
        else{
            if (!isset($filters['detail'])) {
                $search->addQuery(new ExistsQuery('registro_estado_gestion'), BoolQuery::MUST_NOT);
            }
        }
        if (isset($filters['registro_id_estado_econtrol'])) {
            $search->addQuery(
                new TermQuery('registro_id_estado_econtrol', $filters['registro_id_estado_econtrol']),
                BoolQuery::FILTER
            );
        }

        if (isset($filters['registro_id_estado_gestion'])) {
            $search->addQuery(
                new TermQuery('registro_id_estado_gestion', $filters['registro_id_estado_econtrol']),
                BoolQuery::FILTER
            );
        }
        if (isset($filters['franquicia'])) {
            $search->addQuery(
                new MatchQuery('franquicia', $filters['franquicia']),
                BoolQuery::FILTER
            );
        }

        if (isset($filters['registro_verificado'])) {
            $search->addQuery(
                new MatchQuery('registro_verificado', (bool)$filters['registro_verificado']),
                BoolQuery::FILTER
            );
        }

        if (isset($filters['estado'])) {
            $search->addQuery(
                new MatchQuery('estado', $filters['estado']),
                BoolQuery::FILTER
            );
        }

        if (isset($filters['registro_estado_econtrol'])) {
            $search->addQuery(
                new TermQuery('registro_estado_econtrol.keyword', $filters['registro_estado_econtrol']),
                BoolQuery::FILTER
            );
        }

        //agregaciones
        $stateAggregation = new FiltersAggregation('status');
        $states = [
            'Aceptada', 'Pendiente', 'Fallida', 'Rechazada', 'Abandonada', 'Cancelada', 'Reversada', 'Retenida', 'Iniciada', 'Expirada', 'Pre-procesada'
        ];
        foreach ($states as $state) {
            $stateAggregation->addFilter(new TermQuery('estado.keyword', $state), $state);
        }
        $search->addAggregation($stateAggregation);

        $franchiseAggregation = new FiltersAggregation('franchise');
        $franchises = MediosPago::all();
        foreach ($franchises as $franchise) {
            $franchiseAggregation->addFilter(new TermQuery('franquicia.keyword', $franchise->Id), $franchise->nombre);
        }
        $search->addAggregation($franchiseAggregation);

        $stateAggregationControl = new FiltersAggregation('status_control');
        $states = ['Permitida', 'Denegada', 'Alertada y Permitida', 'Alertada y Denegada'];
        foreach ($states as $state) {
            $stateAggregationControl->addFilter(new TermQuery('registro_estado_econtrol.keyword', $state), $state);
        }
        $search->addAggregation($stateAggregationControl);

        $stateAggregationVerified = new FiltersAggregation('verified');
        $states = ['false'=>'No', 'true'=>'Si', ];
        foreach ($states as $key => $state) {
            $bool = new BoolQuery();
            $bool->add(new TermQuery('registro_verificado', $key), BoolQuery::MUST);
            $stateAggregationVerified->addFilter($bool, $state);
        }
        $search->addAggregation($stateAggregationVerified);

        return $search;
    }


    /**
     * @param array $filters
     * @param int $clientId
     * @return array|\ArrayObject|bool|\Countable|float|int|mixed|string|\Traversable|null
     */
    public function searchRecordsControl(array $filters, int $clientId = null) {
        $search = new Search();
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $search->setSize($limit);
        $search->setFrom($page - 1); // Se le resta 1 por que el paginador de elastic la primera pagina comienza por 0.
        if ($search->getFrom() > 0) {
            $search->setFrom(($search->getFrom() * $limit));
        }

        $search->addSort(new FieldSort('registro_id', 'DESC'));

        if ($clientId) {
            $search->addQuery(new TermQuery('cliente_control', $clientId), BoolQuery::FILTER);
        }

        $dateRangeQuery = new RangeQuery('registro_fecha_creacion');

        // Date format = Y-m-d
        if (isset($filters['fromDate'])) {
            $dateRangeQuery->addParameter(RangeQuery::GTE, $filters['fromDate']);
        }

        if (isset($filters['toDate'])) {
            $dateRangeQuery->addParameter(RangeQuery::LTE, $filters['toDate']);
        }

        if (count($dateRangeQuery->getParameters())) {
            $search->addQuery($dateRangeQuery, BoolQuery::FILTER);
        }

        if (isset($filters['registro_id'])) {
            $search->addQuery(
                new TermQuery('registro_id', $filters['registro_id']),
                BoolQuery::FILTER
            );
        }

        //id de transaccion
        if (isset($filters['id'])) {
            $search->addQuery(
                new TermQuery('id', $filters['id']),
                BoolQuery::FILTER
            );
        }
  
        if (isset($filters['registros_estados'])) {
            $search->addQuery(new TermsQuery('registro_id_estado_econtrol', $filters['registros_estados']),
            BoolQuery::FILTER);
        }
        else{
            if (!isset($filters['detail'])) {
                $search->addQuery(new ExistsQuery('registro_estado_gestion'), BoolQuery::MUST_NOT);
            }
        }
        if (isset($filters['registro_id_estado_econtrol'])) {
            $search->addQuery(
                new TermQuery('registro_id_estado_econtrol', $filters['registro_id_estado_econtrol']),
                BoolQuery::FILTER
            );
        }


        if (isset($filters['registro_estado_econtrol'])) {
            $search->addQuery(
                new TermQuery('registro_estado_econtrol.keyword', $filters['registro_estado_econtrol']),
                BoolQuery::FILTER
            );
        }

        $stateAggregationControl = new FiltersAggregation('status_control');
        $states = ['Permitida', 'Denegada', 'Alertada y Permitida', 'Alertada y Denegada'];
        foreach ($states as $state) {
            $stateAggregationControl->addFilter(new TermQuery('registro_estado_econtrol.keyword', $state), $state);
        }
        $search->addAggregation($stateAggregationControl);

        $stateAggregationVerified = new FiltersAggregation('verified');
        $states = ['false'=>'No', 'true'=>'Si', ];
        foreach ($states as $key => $state) {
            $bool = new BoolQuery();
            $bool->add(new TermQuery('registro_verificado', $key), BoolQuery::MUST);
            $stateAggregationVerified->addFilter($bool, $state);
        }
        $search->addAggregation($stateAggregationVerified);

        return $search;
    }


     /**
     * @param array $filters
     * @param int $clientId
     * @return array|\ArrayObject|bool|\Countable|float|int|mixed|string|\Traversable|null
     */
    public function searchTrx(array $filters, array $regs = null, int $clientId =null) {
        $search = new Search();
        $limit = 10000;//$filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $search->setSize($limit);
        $search->setFrom($page - 1); // Se le resta 1 por que el paginador de elastic la primera pagina comienza por 0.
        if ($search->getFrom() > 0) {
            $search->setFrom(($search->getFrom() * $limit));
        }
  
        if ($regs) {
            $search->addQuery(new TermsQuery('id', $regs), BoolQuery::FILTER);
        }

        if ($clientId) {
            $search->addQuery(new TermQuery('id_cliente', $clientId), BoolQuery::FILTER);
        }

        $search->addSort(new FieldSort('id', 'DESC'));
        $dateRangeQuery = new RangeQuery('fecha');

        if (isset($filters['transaccion_id'])) {
            $search->addQuery(
                new TermQuery('id', $filters['transaccion_id']),
                BoolQuery::FILTER
            );
        }

        // Date format = Y-m-d
        if (isset($filters['fromDate'])) {
            $dateRangeQuery->addParameter(RangeQuery::GTE, $filters['fromDate']);
        }

        if (isset($filters['toDate'])) {
            $dateRangeQuery->addParameter(RangeQuery::LTE, $filters['toDate']);
        }

        if (count($dateRangeQuery->getParameters())) {
            $search->addQuery($dateRangeQuery, BoolQuery::FILTER);
        }
 
        if (isset($filters['franquicia'])) {
            $search->addQuery(
                new MatchQuery('franquicia', $filters['franquicia']),
                BoolQuery::FILTER
            );
        }


        if (isset($filters['estado'])) {
            $search->addQuery(
                new MatchQuery('estado', $filters['estado']),
                BoolQuery::FILTER
            );
        }

        $stateAggregation = new FiltersAggregation('status');
        $states = [
            'Aceptada', 'Pendiente', 'Fallida', 'Rechazada', 'Abandonada', 'Cancelada', 'Reversada', 'Retenida', 'Iniciada', 'Expirada', 'pre-procesada'
        ];
        foreach ($states as $state) {
            $stateAggregation->addFilter(new TermQuery('estado.keyword', $state), $state);
        }
        $search->addAggregation($stateAggregation);

        $franchiseAggregation = new FiltersAggregation('franchise');
        $franchises = MediosPago::all();
        foreach ($franchises as $franchise) {
            $franchiseAggregation->addFilter(new TermQuery('franquicia.keyword', $franchise->Id), $franchise->nombre);
        }
        $search->addAggregation($franchiseAggregation);

        return $search;
    }

    /**
     * @param array $filters
     * @param int $clientId
     * @return array|\ArrayObject|bool|\Countable|float|int|mixed|string|\Traversable|null
     */
    public function searchFilters(array $filters, array $regs = null) {
        $search = new Search();
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $search->setSize($limit);
        $search->setFrom($page - 1); // Se le resta 1 por que el paginador de elastic la primera pagina comienza por 0.
        if ($search->getFrom() > 0) {
            $search->setFrom(($search->getFrom() * $limit));
        }

        if ($regs) {
            $search->addQuery(new TermsQuery('transaccion_id', $regs), BoolQuery::FILTER);
        }

        $search->addSort(new FieldSort('transaccion_id', 'DESC'));

        return $search;
    }

    /**
     * @param array $filters
     * @param int $transaction_id
     * @return array|\ArrayObject|bool|\Countable|float|int|mixed|string|\Traversable|null
     */
    public function searchLists(array $filters, int $transaction_id = null) {
        $search = new Search();
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $search->setSize($limit);
        $search->setFrom($page - 1); // Se le resta 1 por que el paginador de elastic la primera pagina comienza por 0.
        if ($search->getFrom() > 0) {
            $search->setFrom(($search->getFrom() * $limit));
        }

        if ($transaction_id) {
            $search->addQuery(new TermQuery('id_transaccion', $transaction_id), BoolQuery::FILTER);
        }

        return $search;
    }

        
}