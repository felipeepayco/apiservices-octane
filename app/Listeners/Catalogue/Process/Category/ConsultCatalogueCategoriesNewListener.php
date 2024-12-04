<?php
namespace App\Listeners\Catalogue\Process\Category;


use App\Exceptions\GeneralException;
use App\Models\Catalogo;
use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Search;
use App\Helpers\Edata\HelperEdata;
use App\Models\CatalogoCategorias;
use App\Http\Validation\Validate as Validate;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use App\Events\Catalogue\Process\Category\ConsultCatalogueProductListEvent;
use App\Events\Catalogue\Process\Category\ConsultCatalogueCategoriesNewEvent;

class ConsultCatalogueCategoriesNewListener extends HelperPago
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
    public function handle(ConsultCatalogueCategoriesNewEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;            
            list($origin,$clientId,$name,$catalogueId,$logo,$id_edata,$edata_estado,$edata_mensaje) = $this->listValidation($fieldValidation);

            $searchCatalogue = new Search();
            $searchCatalogue->setSize(10);
            $searchCatalogue->setFrom(0);

            $searchCatalogue->addQuery(new MatchQuery('id', $catalogueId), BoolQuery::FILTER);
            $searchCatalogue->addQuery(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);

            $catalogueResult = $this->consultElasticSearch($searchCatalogue->toArray(), "catalogo", false);

            
            $this->validateCatalogueCero($catalogueResult);

            $this->validateCategoryExist($origin,$catalogueId,$name,$clientId);

            $catalogueName = $catalogueResult['data'][0]->nombre;

            $urlFile = '';
            if($origin && $logo != ''){ 
                list($urlFile) = $this->uploadAws($logo,$clientId,$catalogueName);                
            }

            $timeArray = explode(" ", microtime());
            $timeArray[0] = str_replace('.','',$timeArray[0]);

            $catalogueCategoriesNew=[
                "id"=> (int)($timeArray[1].substr($timeArray[0],2,3)),
                "nombre"=>$name,
                "cliente_id"=>$clientId,
                "catalogo_id"=>$catalogueId,
                "fecha"=>date("c"),
                "fecha_actualizacion"=>date("c"),
                "img"=>$urlFile,
                "estado"=>true,
                "activo"=>$this->getCategoryIsActive(true,$edata_estado),
                "edata_estado" => $edata_estado
            ];


            //filtro de catalogo para el update_by_query

            $updateCatalogue = $searchCatalogue->toArray();
            unset($updateCatalogue["from"]);
            unset($updateCatalogue["size"]);

            //script del update_by_query con la categoria nueva como parametro
            $updateCatalogue["script"] = [
                "inline"=>"ctx._source.categorias.add(params.categoria)",
                "params"=>[
                    "categoria"=>$catalogueCategoriesNew
                ]
            ];
            $updateCatalogue["indice"] = "catalogo";

            $anukisUpdateCatalogueResponse = $this->elasticUpdate($updateCatalogue);

            if($anukisUpdateCatalogueResponse["success"]){
                $anukisResponseData = json_decode($anukisUpdateCatalogueResponse["data"]->body);
                if($anukisResponseData->updated > 0){
                    $categoryCreate = true;
                }else{
                    $categoryCreate = false;
                }
            }else{
                $categoryCreate = false;
            }

            if ($categoryCreate) {

                list($newData) = $this->newData($catalogueCategoriesNew,$edata_estado,$origin,$catalogueName);
                

                $this->setEpaycoDataResponse($newData,$catalogueCategoriesNew["img"],$origin);

                $success= true;
                $title_response = 'Successful category';
                $text_response = 'successful category';
                $last_action = 'successful category';
                $data = $newData;

                $this->deleteCatalogueRedis($catalogueId);

                // Actualizar el registro edata con el id que se creo
                if (!empty($id_edata)) {
                    $edataSearch = new Search();
                    $edataSearch->addQuery(new MatchQuery('id', $id_edata), BoolQuery::FILTER);
                    $updateData = $edataSearch->toArray();
                    $inlines = [
                        "ctx._source.objeto.id='{$data["id"]}'",
                    ];
                    $updateData["script"] = [
                        "inline" => implode(";", $inlines)
                    ];
                    $updateData["indice"] = "edata_registro";
                    $this->elasticUpdate($updateData);
                }
            } else {
                $success = false;
                $title_response = 'Error in create category';
                $text_response = 'Error in create category';
                $last_action = 'delete category';
                $data = [];
            }
        } catch (GeneralException $generalException){

            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "generalException";
            $arr_respuesta['data'] = $generalException->getData();

            return $arr_respuesta;
        }catch (\Exception $exception) {
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

        if ($edata_estado == HelperEdata::STATUS_ALERT) {
            $arr_respuesta['data']['totalErrors'] = 1;
            $arr_respuesta['data']['errors'] = [
                [
                    'codError' => 'AED100',
                    'errorMessage' => $edata_mensaje,
                ]
            ];
        }

        return $arr_respuesta;
    }


    private function deleteCatalogueRedis ($catalogueId){
        $redis =  app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_'.$catalogueId);
        if ($exist) {
            $redis->del('vende_catalogue_'.$catalogueId);
        }
    }

    private function getCategoryIsActive($active,$categoryStatus){

        if($categoryStatus == HelperEdata::STATUS_ALERT){
            $active = false;
        }

        return $active;
    }

    public function listValidation($fieldValidation){
        $origin = false;
        $clientId = $fieldValidation["clientId"];
        $name=$fieldValidation["name"];
        $catalogueId=$fieldValidation["catalogueId"];
        $logo=isset($fieldValidation["logo"]) ? $fieldValidation["logo"] : "";
        $id_edata = isset($fieldValidation["id_edata"]) ? $fieldValidation["id_edata"] : null;
        $edata_estado = isset($fieldValidation["edata_estado"]) ? $fieldValidation["edata_estado"] : HelperEdata::STATUS_ALLOW;
        $edata_mensaje = isset($fieldValidation["edata_mensaje"]) ? $fieldValidation["edata_mensaje"] : '';
        if($fieldValidation["origin"] == 'epayco'){
            $origin = true;
        }
        return array($origin,$clientId,$name,$catalogueId,$logo,$id_edata,$edata_estado,$edata_mensaje);
    }

    public function validateCatalogueCero($catalogueResult){
        if(count($catalogueResult["data"])==0){
            throw new GeneralException("Catalogue not found",[['codError'=>500,'errorMessage'=>'Catalogue not found']]);
        }
    }

    public function validateCategoryExist($origin, $catalogueId,$categoryName,$clientId){
        if($origin != "epayco" &&
            (getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") && getenv("SOCIAL_SELLER_DUPLICATE_VALIDATION") == "active")){

            $searchCategoryExist = new Search();
            $searchCategoryExist->setSize(1);
            $searchCategoryExist->setFrom(0);

            $boolQuery = new BoolQuery();
            $boolQuery->add(new MatchQuery('cliente_id', $clientId), BoolQuery::FILTER);
            $boolQuery->add(new MatchQuery('id', $catalogueId), BoolQuery::FILTER);

            //preparar nested query
            $boolNestedQuery = new BoolQuery();
            $boolNestedQuery->add(new MatchQuery('categorias.estado', true));
            $boolNestedQuery->add(new RangeQuery('categorias.id',["gte"=>2]));
            $boolNestedQuery->add(new MatchQuery('categorias.nombre.keyword', $categoryName));

            $nestedQuery = new NestedQuery(
                'categorias',
                $boolNestedQuery
            );

            $boolQuery->add($nestedQuery,BoolQuery::MUST);

            // fin preparar nested query

            $searchCategoryExist->addQuery($boolQuery);

            $searchCategoryExistResult = $this->consultElasticSearch($searchCategoryExist->toArray(), "catalogo", false);

            if(count($searchCategoryExistResult["data"])>0){
                throw new GeneralException("category already exist",[['codError'=>500,'errorMessage'=>'Category alredy exist']]);
            }
        }

    }

    public function uploadAws($logo,$clientId,$catalogueName){
        $data = explode(',', $logo);
        $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
        $sacarExt = explode('image/', $data[0]);
        $sacarExt = explode(';', $sacarExt[1]);

        if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
            $success = false;
            $title_response = FORMAT_NOT_ALLOWED;
            $text_response = FORMAT_NOT_ALLOWED;
            $last_action = FORMAT_NOT_ALLOWED;
            $data = [];
            $arr_respuesta['success'] = $success;
            $arr_respuesta['titleResponse'] = $title_response;
            $arr_respuesta['textResponse'] = $text_response;
            $arr_respuesta['lastAction'] = $last_action;
            $arr_respuesta['data'] = $data;
        }
        $base64 = base64_decode($data[1]);
        file_put_contents(
            $tmpfname . "." . $sacarExt[0],
            $base64
        );


        $fechaActual = new \DateTime('now');


        //Subir los archivos
        $nameFile = "{$clientId}_{$catalogueName}_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";
        $urlFile = "vende/productos/{$nameFile}";
        $bucketName = getenv("AWS_BUCKET_MULTIMEDIA_EPAYCO");

        $this->uploadFileAws($bucketName, $tmpfname . "." . $sacarExt[0], $urlFile);
        unlink($tmpfname . "." . $sacarExt[0]);
        return array($urlFile);
    }

    private function setEpaycoDataResponse(&$data,$imageRoute,$origin){

        if($origin){
            $data["logo"] = $imageRoute != "" ? getenv("AWS_BASE_PUBLIC_URL")."/".$imageRoute:"";
            $data["origin"] = 'epayco';
        }

    }

    public function newData($catalogueCategoriesNew,$edata_estado,$origin,$catalogueName){
        $newData = [];
        if($origin){
            $newData = [
                "id"=> $catalogueCategoriesNew["id"],
                "name" => $catalogueCategoriesNew["nombre"],
                "catalogueId"=>$catalogueCategoriesNew["catalogo_id"],
                "catalogueName"=> $catalogueName,
                "img"=>$catalogueCategoriesNew["img"],
                "date" => date("Y-m-d H:i:s", strtotime($catalogueCategoriesNew["fecha"])),
                "edataStatus" => $edata_estado
            ];
        }else{
            $newData = [
                "id"=> $catalogueCategoriesNew["id"],
                "name" => $catalogueCategoriesNew["nombre"],
                "catalogueId"=>$catalogueCategoriesNew["catalogo_id"],
                "date" => date("Y-m-d H:i:s", strtotime($catalogueCategoriesNew["fecha"])),
                "edataStatus" => $edata_estado
            ];
        }

        return array($newData);
    }
}
