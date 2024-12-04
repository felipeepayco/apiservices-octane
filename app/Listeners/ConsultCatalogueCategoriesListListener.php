<?php
namespace App\Listeners;


use App\Events\ConsultCatalogueCategoriesListEvent;
use App\Events\ConsultCatalogueProductListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\CatalogoCategorias;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\CatalogoProductosCategorias;
use Illuminate\Http\Request;

class ConsultCatalogueCategoriesListListener extends HelperPago
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
    public function handle(ConsultCatalogueCategoriesListEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $filters = $fieldValidation["filter"];
            $id=isset($filters->id)?$filters->id:"";
            $name=isset($filters->name)?$filters->name:"";
            $catalogueName=isset($filters->catalogueName)?$filters->catalogueName:"";
            $catalogueId=isset($filters->catalogueId)?$filters->catalogueId:"";
            $onlyWithProducts=isset($filters->onlyWithProducts)?$filters->onlyWithProducts:false;

            $catalogueCategoriesList=CatalogoCategorias::leftJoin("catalogo as c","c.id","=","catalogo_categorias.catalogo_id")
                ->where("catalogo_categorias.estado",1)
                ->where("catalogo_categorias.cliente_id",$clientId)
                ->select("catalogo_categorias.id as id")
                ->addSelect("catalogo_categorias.nombre as name")
                ->addSelect("catalogo_categorias.fecha as date")
                ->addSelect("c.id as catalogueId");

            if($catalogueId!=="")$catalogueCategoriesList->where("c.id",$catalogueId);
            if($catalogueName!=="")$catalogueCategoriesList->where("c.nombre","like","%{$catalogueName}%");
            if($id!=="")$catalogueCategoriesList->where("catalogo_categorias.id",$id);
            if($name!=="")$catalogueCategoriesList->where("catalogo_categorias.nombre","like","%{$name}%");

            $catalogueCategoriesList=$catalogueCategoriesList->orderBy("catalogo_categorias.nombre","asc")->paginate(50);

            $newData = [];

            foreach($catalogueCategoriesList as $catalogueList){
                

                $categorias_producto = CatalogoProductosCategorias::where('catalogo_categoria_id', $catalogueList->id)->get();
                
                if( ($onlyWithProducts && count($categorias_producto) > 0) || !$onlyWithProducts){
                        array_push($newData, $catalogueList);
                }
            }

            $currentData=count($newData)/50;
            if ($newData && count($newData) > 0) {
                $last = $currentData;
                if (!is_int($last)) {
                    $currentData = (int)$last + 1;
                }
            }

            $paginator = new LengthAwarePaginator($newData, count($newData), 50, $currentData);

            $success= true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $paginator;
        } catch (\Exception $exception) {
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
}