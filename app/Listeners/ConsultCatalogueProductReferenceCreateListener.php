<?php
namespace App\Listeners;


use App\Events\ConsultCatalogueProductReferenceCreateEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\CatalogoProductos;
use App\Models\CatalogoProductosReferencias;
use App\Models\CatalogoProductosReferenciasFiles;
use Illuminate\Http\Request;

class ConsultCatalogueProductReferenceCreateListener extends HelperPago
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
    public function handle(ConsultCatalogueProductReferenceCreateEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $name = $fieldValidation["name"];
            $quantity = $fieldValidation["quantity"];
            $amount = $fieldValidation["amount"];
            $catalogue =$fieldValidation["catalogue"];
            $img=$fieldValidation["img"];

            $catalogueProduct= CatalogoProductos::where("id",$catalogue)->where("cliente_id",$clientId)->where("estado",1)->first();
            if(!$catalogueProduct){
                $success = false;
                $title_response = 'catalogue no fount';
                $text_response = 'catalogue no fount';
                $last_action = 'catalogue no fount';
                $data = [];

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;
            }

            $catalogueProductReference=new CatalogoProductosReferencias();
            $catalogueProductReference->nombre=$name;
            $catalogueProductReference->valor=$amount;
            $catalogueProductReference->cantidad=$quantity;
            $catalogueProductReference->disponible=$quantity;
            $catalogueProductReference->estado=true;
            $catalogueProductReference->fecha_creacion=new \DateTime("now");
            $catalogueProductReference->id_catalogo_productos=$catalogueProduct->id;
            $catalogueProductReference->save();

            if($img){
                if(is_object($img)){
                    $img=(array)$img;;
                }
                $totalImg = count($img);
                if($totalImg>10){
                    $success= false;
                    $title_response = 'number of files exceeded';
                    $text_response = 'number of files exceeded';
                    $last_action = 'number of files exceeded';
                    $data = [];
                    $arr_respuesta['success'] = $success;
                    $arr_respuesta['titleResponse'] = $title_response;
                    $arr_respuesta['textResponse'] = $text_response;
                    $arr_respuesta['lastAction'] = $last_action;
                    $arr_respuesta['data'] = $data;

                    return $arr_respuesta;
                }
                if ($totalImg > 0) {
                    for ($k = 1; $k <= $totalImg; $k++) {
                        $data = explode(',', $img["img${k}"]);
                        $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
                        $sacarExt=explode('image/',$data[0]);
                        $sacarExt=explode(';',$sacarExt[1]);

                        if($sacarExt[0]!="jpg"&&$sacarExt[0]!="jpeg"&&$sacarExt[0]!=="png"){
                            $success= false;
                            $title_response = 'file format not allowed';
                            $text_response = 'file format not allowed';
                            $last_action = 'file format not allowed';
                            $data = [];
                            $arr_respuesta['success'] = $success;
                            $arr_respuesta['titleResponse'] = $title_response;
                            $arr_respuesta['textResponse'] = $text_response;
                            $arr_respuesta['lastAction'] = $last_action;
                            $arr_respuesta['data'] = $data;
                        }
                        $base64=base64_decode($data[1]);
                        file_put_contents(
                            $tmpfname.".".$sacarExt[0],
                            $base64
                        );



                        $fechaActual = new \DateTime('now');


                        //Subir los archivos
                        $nameFile = "{$clientId}_cobros_files_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";
                        $urlFile = "catalogo/files/{$nameFile}";

                        $catalogueProductReferenceFile=new CatalogoProductosReferenciasFiles();
                        $catalogueProductReferenceFile->url=$urlFile;
                        $catalogueProductReferenceFile->id_catalogo_productos_referencias=$catalogueProductReference->id;
                        $catalogueProductReferenceFile->save();

                        //Subir a rackespace
                        $this->uploadFile('media', $tmpfname.".".$sacarExt[0], $nameFile, 'catalogo/files');

                        unlink($tmpfname.".".$sacarExt[0]);
                    }
                }
            }

            $imgs=CatalogoProductosReferenciasFiles::where("id_catalogo_productos_referencias",$catalogueProductReference->id)
                ->where("estado",1)
                ->get()->toArray();
            $imgsReturn=[];
            foreach ($imgs as $key=>$img){
             $imgReturn[]=array(
                    "id"=>$img["id"],
                    "url"=>"https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/".$img["url"]
                );
            }

            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = [
                "id"=>$catalogueProductReference->id,
                "name"=>$catalogueProductReference->nombre,
                "amount"=>$catalogueProductReference->valor,
                "quantity"=>$catalogueProductReference->cantidad,
                "available"=>$catalogueProductReference->disponible,
                "catalogueId"=>$catalogueProductReference->id_catalogo_productos,
                "imgs"=>$imgsReturn,
            ];


        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Unexpected error when querying reference with data parameters";
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
}