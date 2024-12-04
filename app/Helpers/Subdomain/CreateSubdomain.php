<?php

namespace App\Helpers\Subdomain;

use App\Http\Controllers\Controller as Controller;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Messages\CommonText;
use App\Events\Miepayco\Process\ProcessMiepaycoSaveDataEvent;
use App\Models\Clientes;
use App\Models\ClientesMiEpayco;
use App\Models\DetalleConfClientes;
use App\Models\RecaudoProyecto;
use App\Events\ConsultSubdomainCreateEvent;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use App\Exceptions\GeneralException;


class CreateSubdomain extends HelperPago{
    public $clientId;
    public $domain;
    const ACTIVE_STATUS = 1;
    

    public function createSubdomainClient($clientId, $domain){
            
        try {

                
                $searchCatalogueFirst = new Search();
                $searchCatalogueFirst->setSize(5000);
                $searchCatalogueFirst->setFrom(0);
                $searchCatalogueFirst->addQuery(new MatchQuery(CommonText::CLIENT_ID, $clientId), BoolQuery::FILTER);
    
                $catalogueExistResult = $this->consultElasticSearch($searchCatalogueFirst->toArray(), "catalogo", false);
                
                $recaudoProyecto = null;
            
                $recaudoProyecto = RecaudoProyecto::where("id_cliente", $clientId)
                    ->where("estado", '!=', 'eliminado')
                    ->first();                

                $miEpayco = ClientesMiEpayco::where('cliente_id', '=', $clientId)
                            ->where('estado', '=', self::ACTIVE_STATUS)
                            ->first();
                $countCatalogue = count($catalogueExistResult["data"]);

                if (isset($catalogueExistResult["data"]) 
                    && $countCatalogue >= 0
                    && $recaudoProyecto
                    && !$miEpayco
            ) {
                    //Crear el subdominio del cliente por que no tiene ningun catalogo, pero validando que ya tenga subdominio configurado.
                    
                    return event(new ConsultSubdomainCreateEvent(
                        [
                            CommonText::CLIENTID => $clientId, 
                            "subdomain" => $domain
                        ]
                    ));
                }

        } catch (GeneralException $error) {
            return $error->getMessage();
        }

    }
}