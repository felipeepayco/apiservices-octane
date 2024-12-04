<?php
namespace App\Listeners;


use App\Events\ConsultCatalogueTopSellingProductsListEvent;
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
use App\Helpers\Messages\CommonText;

class ConsultCatalogueTopSellingProductsListListener extends HelperPago {

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
    public function handle(ConsultCatalogueTopSellingProductsListEvent $event)
    {
        try{
            $fieldValidation = $event->arr_parametros;
            $clientId=$fieldValidation["clientId"];
            $filters=$fieldValidation["filter"];
            $id=isset($filters->id)?$filters->id:"";
            $currency=isset($filters->currency)?$filters->currency:"";
            $reference=isset($filters->reference)?$filters->reference:"";
            $title=isset($filters->title)?$filters->title:"";
            $amount=isset($filters->amount)?$filters->amount:"";
            $catalogueId=isset($filters->catalogueId)?$filters->catalogueId:"";
            $fechaInicion=isset($filters->initialDate)?$filters->initialDate:"";
            $fechaFin=isset($filters->finalDate)?$filters->finalDate:"";
            $categorieId = isset($filters->categorieId)?$filters->categorieId:"";
            $sellList= CatalogoProductos::orderBy("ventas","desc")->where("cliente_id",$clientId)->where("estado",">=",1);


            if($id!="")$sellList=$sellList->where("id","=", $id);
            if($currency!="")$sellList=$sellList->where("moneda","LIKE", "%{$currency}%");
            if($reference!="")$sellList=$sellList->where("numerofactura","LIKE", "%{$reference}%");
            if($title!="")$sellList=$sellList->where("titulo","LIKE", "%{$title}%");
            if($amount!="")$sellList=$sellList->where("valor","LIKE", "%{$amount}%");
            if($catalogueId!="")$sellList=$sellList->where("catalogo_id","=", $catalogueId);
            if($fechaInicion!="")$sellList=$sellList->where("fecha",">=", $fechaInicion." 000:00:00");
            if($fechaFin!="")$sellList=$sellList->where("fecha","<=", $fechaFin." 23:59:59");

            $newData=[];
            $cobros=$sellList->addSelect("id")
                ->addSelect("fecha")
                ->addSelect("titulo")
                ->addSelect("descripcion")
                ->addSelect("numerofactura")
                ->addSelect("moneda")
                ->addSelect("valor")
                ->addSelect("estado")
                ->addSelect("id")
                ->addSelect("txtcodigo")
                ->addSelect("cliente_id")
                ->addSelect("cantidad")
                ->addSelect("disponible")
                ->addSelect("moneda")
                ->addSelect("url_confirmacion")
                ->addSelect("url_respuesta")
                ->addSelect("iva")
                ->addSelect("numerofactura")
                ->addSelect("fecha_expiracion")
                ->addSelect("catalogo_id")
                ->addSelect("nombre_contacto")
                ->addSelect("numero_contacto")
                ->addSelect("ventas")
                ->orderBy("fecha", "desc")->get();

            foreach ($cobros as $cobro ){

                $im=CatalogoProductosFiles::where("catalogo_productos_id",$cobro->id)
                    ->where("estado",1)->select("url")->get()->toArray();
                $imagenes=[];
                foreach ($im as $ima){
                    $imagenes[]=getenv("RACKSPACE_CONTAINER_BASE_PUBLIC_URL")."/".$ima["url"];
                }
                $productReferencesArray=[];
                $tiposEnviosArray=[];

                $catalogueProductReference = CatalogoProductosReferencias::
                where("id_catalogo_productos", $cobro->id)
                    ->where("estado",1)->get()->toArray();

                $catalogo = Catalogo::find($cobro->catalogo_id);
                foreach ($catalogueProductReference as $first=>$catalogueProductReferenceInsert){
                    $catalogueProductReferenceInsert=(object)$catalogueProductReferenceInsert;

                    $urltxtcodigo = "https://default.epayco.me/catalogo/{$catalogo->nombre}/{$catalogueProductReferenceInsert->id}";
                    $cobro->disponible=$catalogueProductReferenceInsert->disponible+($first===0?0:$cobro->disponible);
                    $url_qr = getenv("BASE_URL_REST")."/".getenv("BASE_URL_APP_REST_ENTORNO").CommonText::PATH_TEXT_CODE . $urltxtcodigo;
                    $productReferencesArray[] = [
                        "id" => $catalogueProductReferenceInsert->id,
                        "title" => $catalogueProductReferenceInsert->nombre,
                        "quantity" => $catalogueProductReferenceInsert->cantidad,
                        "available" => $catalogueProductReferenceInsert->disponible,
                        "amount" => $catalogueProductReferenceInsert->valor,

                        "date" => (new \DateTime($catalogueProductReferenceInsert->fecha))->format("Y-m-d H:i:s"),
                        "txtCode" => $catalogueProductReferenceInsert->txtcodigo,
                        "baseTax" => $catalogueProductReferenceInsert->base_iva,
                        "description" => $catalogueProductReferenceInsert->descripcion,
                        "currency" => $catalogueProductReferenceInsert->moneda,
                        "urlConfirmation" => $catalogueProductReferenceInsert->url_confirmacion,
                        "urlResponse" => $catalogueProductReferenceInsert->url_respuesta,
                        "tax" => $catalogueProductReferenceInsert->iva,
                        "invoiceNumber" => $catalogueProductReferenceInsert->numerofactura,
                        "expirationDate" => $catalogueProductReferenceInsert->fecha_expiracion,
                        "routeQr" => $url_qr,
                        "routeLink" => $urltxtcodigo,

                        "img" => isset($imgReturn) ? $imgReturn : [],

                    ];
                }

                $tiposEnviosCatalogoProductos=CatalogoProductosEnvio::
                leftJoin("catalogo_tipo_envio as cte","cte.id","=","catalogo_productos_envio.id_catalogo_tipo_envio")
                ->where("id_catalogo_productos",$cobro->id)
                    ->where("cte.estado",1)
                    ->where("catalogo_productos_envio.estado",1)
                    ->get()->toArray();
                foreach ($tiposEnviosCatalogoProductos as $tiposEnviosCatalogoProducto){
                    $tiposEnviosCatalogoProducto=(object)$tiposEnviosCatalogoProducto;
                    $tiposEnviosArray[] = [
                        "type" => $tiposEnviosCatalogoProducto->nombre,
                        "amount" => $tiposEnviosCatalogoProducto->valor
                    ];
                }



                $categorias_producto = CatalogoProductosCategorias::where('catalogo_producto_id', $cobro->id)->get();

                $arr_categorias = array();

                if (count($categorias_producto) > 0) {
                    foreach ($categorias_producto as $categoria)
                        $arr_categorias[] = $categoria->catalogo_categoria_id;
                }

                if( $categorieId!="" && !in_array($categorieId,$arr_categorias))continue;



                $txtcodigo = str_pad($cobro->id, '5', "0", STR_PAD_LEFT);

                $url = 'secure.payco.co';//$this->getRequest()->getHttpHost();
                $url2 = '/apprest';
                $rutaqr = $url . $url2 . '/images/QRcodes/' . $txtcodigo . '.png';
                $urltxtcodigo = "https://default.epayco.me/catalogo/{$catalogo->nombre}/{$cobro->id}";

                $url_qr = getenv("BASE_URL_REST")."/".getenv("BASE_URL_APP_REST_ENTORNO").CommonText::PATH_TEXT_CODE . $urltxtcodigo;

                //ToDo: Se debe cambiar este valor por las ventas del ultimo mes cuando se guarde esta informacion en db
                $ventasUltimoMes=0;

                $newData[] = [
                    "date" => $cobro->fecha->format("Y-m-d H:i:s"),
                    "state" => $cobro->estado,
                    "txtCode" => $cobro->txtcodigo,
                    "clientId" => $cobro->cliente_id,
                    "available" => $cobro->disponible,
                    "lastMonthSales"=>$ventasUltimoMes,
                    "baseTax" => $cobro->base_iva,
                    "ventas" => $cobro->ventas,
                    "description" => $cobro->descripcion,
                    "title" => $cobro->titulo,
                    "currency" => $cobro->moneda,
                    "urlConfirmation" => $cobro->url_confirmacion,
                    "urlResponse" => $cobro->url_respuesta,
                    "tax" => $cobro->iva,
                    "amount" => $cobro->valor,
                    "invoiceNumber" => $cobro->numerofactura,
                    "expirationDate" => $cobro->fecha_expiracion,
                    "contactName" => $cobro->nombre_contacto,
                    "contactNumber" => $cobro->numero_contacto,
                    "routeQr" => $url_qr,
                    "routeLink" => $urltxtcodigo,
                    "id" => $cobro->id,
                    "img"=>$imagenes,
                    "shippingTypes" => $tiposEnviosArray,
                    "categories" => $arr_categorias,
                    "references" => $productReferencesArray
                ];
            }


            $success= true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $newData;


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
}