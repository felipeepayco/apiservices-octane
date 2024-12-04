<?php
namespace App\Service\V2\Product\Process;

use App\Exceptions\GeneralException;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use App\Repositories\V2\ProductRepository;
use App\Helpers\Pago\HelperPago;
use Exception;


class ToggleProductService extends HelperPago
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


    try {

      $idProd = $data['id'];
      $clientId = $data['clientId'];

      $filter = (object) ['id' => $idProd];
      list($products) = $this->productRepository->listProductoFilter(['filter' => $filter, 'clientId' => $clientId]);

      //Verifica si el producto Activo existe
      if ($products->count()) {
          $action = !$products[0]->activo;


        $this->validateLastProductInCatalogue($products, $clientId);
        $updateProductoResponse = $this->productRepository->update($idProd, ['activo' => $action]);
        if ($updateProductoResponse) {
          $productUpdated = true;
          $this->deleteCatalogueRedis($products[0]->catalogo_id);
        } else {
          $productUpdated = false;
        }

        if ($productUpdated) {
          $success = true;
          $title_response = "Successful " . ($action ? "active" : "inactive") . " product";
          $text_response = "successful " . ($action ? "active" : "inactive") . " product";
          $last_action = "" . ($action ? "active" : "inactive") . " product";
          $data = [
            "success" => true,
            "titleResponse" => "Successful " . ($action ? "active" : "inactive") . " product",
            "textResponse" => "successful " . ($action ? "active" : "inactive") . " product",
            "lastAction" => "" . ($action ? "active" : "inactive") . " product",
            "data" => [
              "active" => $action
            ]
          ];
        } else {
          $success = false;
          $title_response = 'Error inactive product';
          $text_response = 'Error inactive product, product not found';
          $last_action = 'inactive product';
          $data = [];
        }

      } else {
        //Resultado si no encuentra el producto
        $success = false;
        $title_response = 'Error';
        $text_response = "Error activeInactive product, product not found";
        $last_action = 'fetch data from database';
        $error = (object) $this->getErrorCheckout('E0100');
        $validate = (object) new Validate();
        $validate->setError($error->error_code, $error->error_message);
        $data = array(
          'totalerrores' => $validate->totalerrors,
          'errores' =>
            $validate->errorMessage
        );
      }


    } catch (Exception $exception) {
      $success = false;
      $title_response = 'Error';
      $text_response = "Producto no pudo ser activado";
      $last_action = 'fetch data from database';
      $error = (object) $this->getErrorCheckout('E0100');
      $validate = (object) new Validate();
      $validate->setError($error->error_code, $error->error_message);
      $data = array(
        'totalerrores' => $validate->totalerrors,
        'errores' =>
          $validate->errorMessage
      );

    }

    $arr_respuesta['success'] = $success;
    $arr_respuesta['titleResponse'] = $title_response;
    $arr_respuesta['textResponse'] = $text_response;
    $arr_respuesta['lastAction'] = $last_action;
    $arr_respuesta['data'] = $data;

    return $arr_respuesta;
  }


  private function validateLastProductInCatalogue($product, $clientId)
  {
    $filter = (object) ['catalogueId' => $product[0]->catalogo_id];
    list($products) = $this->productRepository->listProductoFilter(['filter' => $filter, 'clientId' => $clientId]);
    $cant=$products->count();
    if ($cant==1) {
      $this->catalogueRepository->updateWithClientId($product[0]->catalogo_id, $clientId, ['progreso' => 'completado']);
    }
  }

  private function deleteCatalogueRedis($catalogueId)
  {
    $redis = app('redis')->connection();
    $exist = $redis->exists('vende_catalogue_' . $catalogueId);
    if ($exist) {
      $redis->del('vende_catalogue_' . $catalogueId);
    }
  }
}