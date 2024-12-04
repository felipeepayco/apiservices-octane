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

class DeleteProductService extends HelperPago
{
  protected CatalogueRepository $catalogueRepository;
  protected ProductRepository $productRepository;
  protected ClientRepository $clientRepository;

  // public Request $rq;

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
      list($product) = $this->productRepository->listProductoFilter(['filter' => $filter, 'clientId' => $clientId]);

      if (count($product) > 0) {
        $this->validateLastProductInCatalogue($product, $clientId);

        $updateProductoResponse = $this->productRepository->update($idProd, ['estado' => 0]);
        if ($updateProductoResponse) {
          $productUpdated = true;
          $this->deleteCatalogueRedis($product[0]->catalogo_id);
        } else {
          $productUpdated = false;
        }

        if ($productUpdated) {
          $success = true;
          $title_response = 'Successful delete product';
          $text_response = 'successful delete product';
          $last_action = 'delete product';
          $data = [
            "success" => true,
            "titleResponse" => "Successful delete product",
            "textResponse" => "successful delete product",
            "lastAction" => "delete product",
            "data" => []
          ];
        } else {
          $success = false;
          $title_response = 'Error delete product';
          $text_response = 'Error delete product, product not found';
          $last_action = 'delete sell';
          $data = [];
        }

      } else {
        $success = false;
        $title_response = 'Error';
        $text_response = "Error delete product, product not found";
        $last_action = 'fetch data from database';
        $error = (object) $this->getErrorCheckout('E0100');
        $validate = new Validate();
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
      $text_response = "Error delete product".$exception->getMessage();
      $last_action = 'fetch data from database';
      $error = (object) $this->getErrorCheckout('E0100');
      $validate = new Validate();
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

    if (count($products) == 1) {
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