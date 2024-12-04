<?php
namespace App\Service\V2\Product\Process;

use App\Exceptions\GeneralException;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use App\Repositories\V2\ProductRepository;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\ValidateUrlImage;
use Exception;


class TopSellingProductService extends HelperPago
{
  protected CatalogueRepository $catalogueRepository;
  protected ProductRepository $productRepository;
  protected ClientRepository $clientRepository;


  public function __construct(CatalogueRepository $catalogueRepository, ProductRepository $productRepository, ClientRepository $clientRepository)
  {
    $this->catalogueRepository = $catalogueRepository;
    $this->productRepository = $productRepository;
    $this->clientRepository = $clientRepository;
  }

  public function process($data)
  {
    try{

        $data['filter'] = (object)['order'=>'topSelling'];
        list($products,$clientId) = $this->productRepository->listProductoFilter($data);


        $success= true;
        $title_response = 'Successful consult';
        $text_response = 'successful consult';
        $last_action = 'successful consult';
        $data = [];
        foreach($products as $key => $value){
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
                $data[$key]['img'][$ki] = ValidateUrlImage::locateImage($img);
            }
            $data[$key]['shippingTypes'] = [];
            $data[$key]['categories'] = [];
            if(isset($value->referencias) && count($value->referencias) > 0){
                if($value->referencias[0]['id'] != null){
                    foreach($value->referencias as $ref){
                        $available = $available + $ref['disponible'];
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
        $title_response = 'Error'.$exception->getFile();
        $text_response = "Error query to database".$exception->getMessage();
        $last_action = 'fetch data from database'.$exception->getLine();
        $error = (object) $this->getErrorCheckout('E0100');
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