<?php
namespace App\Listeners\Catalogue\Process\Product;


use App\Events\ConsultCatalogueTopSellingProductsListElasticEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\Catalogo;
use App\Models\CatalogoProductos;
use App\Models\CatalogoProductosCategorias;
use App\Models\CatalogoProductosEnvio;
use App\Models\CatalogoProductosFiles;
use App\Models\CatalogoProductosReferencias;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

class ConsultCatalogueTopSellingProductsListElasticListener extends HelperPago {

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
    public function handle(ConsultCatalogueTopSellingProductsListElasticEvent $event)
    {
        try{

            $fieldValidation = $event->arr_parametros;
            list($invoices,$clientId) = $this->searchTopProduct($fieldValidation);


            $success= true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = [];
            foreach($invoices['data'] as $key => $value){
                $available = 0;
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
                $data[$key]['amount'] = $value->valor;
                $data[$key]['invoiceNumber'] = $value->numerofactura;
                $data[$key]['expirationDate'] = $value->fecha_expiracion;
                $data[$key]['contactName'] = $value->nombre_contacto;
                $data[$key]['contactNumber'] = $value->numero_contacto;
                $data[$key]['routeQr'] = $value->id;
                $data[$key]['routeLink'] = $value->id;
                $data[$key]['id'] = $value->id;
                foreach ($value->img as $ki => $img) {
                    $data[$key]['img'][$ki] = getenv("AWS_BASE_PUBLIC_URL").'/'.$img;
                }
                $data[$key]['shippingTypes'] = [];
                $data[$key]['categories'] = [];
                if(isset($value->referencias) && count($value->referencias) > 0){
                    if($value->referencias[0]->id != null){
                        foreach($value->referencias as $ref){
                            $available = $available + $ref->disponible;
                        }
                    }
                }else{
                    $available = $value->disponible;
                }
                $data[$key]['available'] = $available;
                $data[$key]['references'] = [];
            }


        }catch (Exception $exception){
            $success = false;
            $title_response = 'Error';
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
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;

    }
    public function searchTopProduct($fieldValidation){

        $search = new Search();
        $search->setSize(12);
        $search->setFrom(0);

        $clientId=$fieldValidation["clientId"];
        $search->addQuery(new MatchQuery('cliente_id', $clientId/* 100631 */), BoolQuery::FILTER);
        $search->addQuery(new MatchQuery('estado', 1), BoolQuery::FILTER);

        //filtros
        if (isset($fieldValidation["filter"]->id))             $search->addQuery(new MatchQuery('id', $fieldValidation["filter"]->id), BoolQuery::FILTER) ;
        if (isset($fieldValidation["filter"]->title))          $search->addQuery(new MatchQuery('titulo', $fieldValidation["filter"]->title), BoolQuery::FILTER) ;
        if (isset($fieldValidation["filter"]->invoiceNumber))  $search->addQuery(new MatchQuery('numerofactura', $fieldValidation["filter"]->invoiceNumber), BoolQuery::FILTER) ;
        if (isset($fieldValidation["filter"]->description))    $search->addQuery(new MatchQuery('descripcion', $fieldValidation["filter"]->description), BoolQuery::FILTER) ;
        if (isset($fieldValidation["filter"]->amount))         $search->addQuery(new MatchQuery('valor', $fieldValidation["filter"]->amount), BoolQuery::FILTER) ;
        if (isset($fieldValidation["filter"]->currency))       $search->addQuery(new MatchQuery('moneda', $fieldValidation["filter"]->currency), BoolQuery::FILTER) ;
        if (isset($fieldValidation["filter"]->tax))            $search->addQuery(new MatchQuery('iva', $fieldValidation["filter"]->tax), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->baseTax))        $search->addQuery(new MatchQuery('base_iva', $fieldValidation["filter"]->baseTax), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->discountPrice))  $search->addQuery(new MatchQuery('precio_descuento', $fieldValidation["filter"]->discountPrice), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->onePayment))     $search->addQuery(new MatchQuery('cobrounico', $fieldValidation["filter"]->onePayment), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->quantity))       $search->addQuery(new MatchQuery('cantidad', $fieldValidation["filter"]->quantity), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->available))      $search->addQuery(new MatchQuery('disponible', $fieldValidation["filter"]->available), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->expirationDate)) $search->addQuery(new MatchQuery('fecha_expiracion', $fieldValidation["filter"]->expirationDate), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->urlResponse))    $search->addQuery(new MatchQuery('url_respuesta', $fieldValidation["filter"]->urlResponse), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->urlConfirmation))$search->addQuery(new MatchQuery('url_confirmacion', $fieldValidation["filter"]->urlConfirmation), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->catalogueId))    $search->addQuery(new MatchQuery('catalogo_id', $fieldValidation["filter"]->catalogueId), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->contactName))    $search->addQuery(new MatchQuery('nombre_contacto', $fieldValidation["filter"]->contactName), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->contactNumber))  $search->addQuery(new MatchQuery('numero_contacto', $fieldValidation["filter"]->contactNumber), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->sales))          $search->addQuery(new MatchQuery('ventas', $fieldValidation["filter"]->sales), BoolQuery::FILTER);
        if (isset($fieldValidation["filter"]->categorieId))    $search->addQuery(new MatchQuery('categorias', $fieldValidation["filter"]->categorieId), BoolQuery::FILTER) ;
        //if (isset($fieldValidation["categories"])) $search->addQuery(new MatchQuery('cliente_id', $fieldValidation["categories"]), BoolQuery::FILTER);

        ///ORDER
        $search->addSort(new FieldSort('ventas', 'DESC'));
        $query = $search->toArray();

        $invoices = $this->consultElasticSearch($query, "producto", false);
        return array($invoices, $clientId);


    }
}
