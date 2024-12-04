<?php
namespace App\Listeners;


use App\Events\ConsultCatalogueCategoriesListEvent;
use App\Events\ConsultCatalogueCategoriesNewEvent;
use App\Events\ConsultCatalogueProductListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\Catalogo;
use App\Models\CatalogoCategorias;
use Illuminate\Http\Request;

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
            $clientId = $fieldValidation["clientId"];
            $name=$fieldValidation["name"];
            $catalogueId=$fieldValidation["catalogueId"];


            $catalogue=Catalogo::where("id",$catalogueId)
                ->where("cliente_id",$clientId)
                ->first();



            if(!$catalogue){
                $validate = new Validate();
                $validate->setError(500, "catalogue not found");
                $success         = false;
                $last_action    = 'validation clientId y data of filter';
                $title_response = 'Error';
                $text_response  = 'Some fields are required, please correct the errors and try again';

                $data           =
                    array('totalerrors'=>$validate->totalerrors,
                        'errors'=>$validate->errorMessage);
                $response=array(
                    'success'         => $success,
                    'titleResponse' => $title_response,
                    'textResponse'  => $text_response,
                    'lastAction'    => $last_action,
                    'data'           => $data
                );

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;

            }

            $category=CatalogoCategorias::where("nombre",$name)
                ->where("cliente_id",$clientId)
                ->where("estado",1)
                ->first();
            if($category){
                $validate = new Validate();
                $validate->setError(500, "category already exist");
                $success         = false;
                $last_action    = 'validation clientId y data of filter';
                $title_response = 'Error';
                $text_response  = 'Some fields are required, please correct the errors and try again';

                $data           =
                    array('totalerrors'=>$validate->totalerrors,
                        'errors'=>$validate->errorMessage);
                $response=array(
                    'success'         => $success,
                    'titleResponse' => $title_response,
                    'textResponse'  => $text_response,
                    'lastAction'    => $last_action,
                    'data'           => $data
                );

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;
            }

            $catalogueCategoriesNew=new CatalogoCategorias();
            $catalogueCategoriesNew->nombre=$name;
            $catalogueCategoriesNew->cliente_id=$clientId;
            $catalogueCategoriesNew->catalogo_id=$catalogueId;
            $catalogueCategoriesNew->fecha=new \DateTime("now");
            $catalogueCategoriesNew->estado=1;
            $catalogueCategoriesNew->save();


             $newData = [
                "id"=> $catalogueCategoriesNew->id,
                "name" => $catalogueCategoriesNew->nombre,
                "catalogueId"=>$catalogueCategoriesNew->catalogo_id,
                "date" => $catalogueCategoriesNew->fecha->format("Y-m-d H:i:s"),
            ];

            $success= true;
            $title_response = 'Successful category';
            $text_response = 'successful category';
            $last_action = 'successful category';
            $data = $newData;

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