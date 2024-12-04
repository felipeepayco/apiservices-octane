<?php


namespace App\Listeners;


use App\Helpers\Pago\HelperPago;
use App\Events\ValidationGeneralClientListKeysEvent;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Models\Clientes;
use App\Models\DetalleConfClientes;
use App\Models\BblClientes;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
class ValidationGeneralClientListKeysListener extends HelperPago
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
    public function handle(ValidationGeneralClientListKeysEvent $event)
    {
        try{

            $fieldValidation = $event->arr_parametros;

            if(!isset($fieldValidation["url"]) || $fieldValidation["url"] == ""){
                $arrResponse['success'] = false;
                $arrResponse['cod_error'] = 404;
                $arrResponse['titleResponse'] = "Invalid subdomain";
                $arrResponse['textResponse'] = "Invalid subdomain";
                $arrResponse['lastAction'] = "ValidationClientListKeys";
                $arrResponse['data'] = ["error" => "Invalid subdomain"];

                return $arrResponse;
            }
            

            $domainConfig=$this->buscarDominio($fieldValidation["url"]);


            if($fieldValidation["clientId"] != getenv("CLIENT_ID_BABILONIA")){
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = "Unauthorized";
                $arrResponse['textResponse'] = "Unauthorized";
                $arrResponse['lastAction'] = "ValidationClientListKeys";
                $arrResponse['data'] = ["error" => "Unauthorized"];

                return $arrResponse;
            }

            if(is_null($domainConfig)){
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = "Subdomain or domain not exist";
                $arrResponse['textResponse'] = "Subdomain or domain  not exist";
                $arrResponse['lastAction'] = "ValidationClientListKeys";
                $arrResponse['data'] = ["error" => "Subdomain or domain not exist"];

                return $arrResponse;
            }

            $success = true;
            $title_response = 'Valid Subdomain';
            $text_response = 'Valid Subdomain';
            $last_action = 'Subdomain';
            $data = $domainConfig;

        }catch(\Exception $exception){
            $success = false;
            $title_response = 'Error';
            $text_response = "Error query to database";
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
    private function buscarDominio($url){
        $urlCompleta=$this->eliminarHttps($url);

        $urlCompleta=strtolower($urlCompleta);

        //buscar dominio propio
        $res=$this->buscarDominioPropio($urlCompleta);
        if($res['status']){
         if(count($res['data'])>0){
             $domainConfig=$res['data'][0];
           
         }
        }
        
        //buscar sub dominio epayco
        if(!isset($domainConfig)){

            $pattern = '/(.*?)\.(?=[^\/]*\..{2,3})/';

            if (preg_match($pattern, $urlCompleta, $match)) {
                $url=$match[0];
    
                $domain=config('app.BASE_URL_EPAYCO');
                $domainConfig=$this->consultarDominio($url,$domain);
        
                if(is_null($domainConfig)){
                    $domain=config('app.BASE_URL_BBL');
                    $domainConfig=$this->consultarDominio($url,$domain);
                }
            } 
        }
       
      

    return $domainConfig;
    }
    private function buscarDominioPropio($url){
    
        $dominio=$this->agregarWWW($url);
        $res= $this->consultaElastic($dominio);
        return $res;    

    }
    private function eliminarHttps($url){
        $url = str_replace("https://", "", $url);
        $url = str_replace("http://", "", $url);
        $url = str_replace("//", "", $url);
        return $url;
    }
    private function consultaElastic($dominio){
        $arrDominio=explode(".",$dominio);
        $ownSubDomainValue=$arrDominio[0];
        $ownDomainValue= str_replace($ownSubDomainValue.".", '', $dominio);
        $search = new Search();
        $search->setSize(1);
        $search->setFrom(0);
        $search->addQuery(new MatchQuery('valor_dominio_propio',$ownDomainValue), BoolQuery::FILTER);
        $search->addQuery(new MatchQuery('valor_subdominio_propio', $ownSubDomainValue), BoolQuery::FILTER);
        return  $this->consultElasticSearch($search->toArray(), "catalogo", false);
    }
    private function extraerSubDominio($url){
        $arrUrl=explode(".",$url);
        return $arrUrl[0];
    }
    private function extraerDominio($url){
        $arrUrl=explode(".",$url);
        $arrUrlB=explode("//",$arrUrl[0]);
        return $arrUrlB[1];
    }
    private function agregarWWW($url){
        $subDominio=$this->extraerSubDominio($url);

        if($subDominio=="www"){
            return $url;
        }else{
            $cant=count(explode(".",$url));
            if($cant<3){
                $domain = parse_url($url, PHP_URL_HOST);
                return "https://www.".$domain;
            }else{
                return $url;
            }
        }
       
    }

    private function consultarDominio($url,$domain){
        $domain = parse_url($domain, PHP_URL_HOST);
        
        $url=$url.$domain;
        $domainConfig = BblClientes::where("url","like","%".$url)
            ->first();

        return $domainConfig;

    }

}