<?php

namespace App\Service\V2\Catalogue\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\BblBuyerRepository;
use App\Repositories\V2\DiscountCodeRepository;
use App\Repositories\V2\ShoppingCartRepository;



use Illuminate\Support\Facades\Log;

class CatalogueDeleteService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;
    protected ProductRepository $productRepository;
    protected BblBuyerRepository $bblBuyerRepository;
    protected DiscountCodeRepository $discountCodeRepository;
    protected ShoppingCartRepository $shoppingCartRepository;

    public function __construct(
    CatalogueRepository $catalogueRepository, 
    ProductRepository $productRepository,
    BblBuyerRepository $bblBuyerRepository,
    DiscountCodeRepository $discountCodeRepository,
    ShoppingCartRepository $shoppingCartRepository,

    )
    {
        $this->catalogueRepository = $catalogueRepository;
        $this->productRepository = $productRepository;
        $this->bblBuyerRepository = $bblBuyerRepository;
        $this->discountCodeRepository = $discountCodeRepository;
        $this->shoppingCartRepository = $shoppingCartRepository;

    }
    public function process($data)
    {

        try {
            $fieldValidation = $data;
            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];

            $catalogueExistResult = $this->catalogueRepository->findByIdAndClientId($id, $clientId);

            if ($catalogueExistResult > 0) {
                $data["estado"] = false;
                $result = $this->catalogueRepository->update($id, $data);

                if ($result) {
                    $catalogueDelete = $this->deleteCatalogueRedis($id);
                } else {
                    $catalogueDelete = false;
                }
            } else {
                $catalogueDelete = false;
            }

            if ($catalogueDelete) {
                $this->disabledCatalogueProducts($id);
                $this->deleteBuyers($clientId);
                $this->deleteDiscountCodes($clientId);
                $this->disableShoppingCarts($id);
                
                $success = true;
                $title_response = 'Successful delete catalogue';
                $text_response = 'successful delete catalogue';
                $last_action = 'delete catalogue';
                $data = [];
            } else {
                $success = false;
                $title_response = 'Error delete catalogue';
                $text_response = 'Error delete catalogue, catalogue not found';
                $last_action = 'delete catalogue';
                $data = [];
            }
        } catch (\Exception $exception) {

            Log::info($exception);
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado " . $exception->getMessage();
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' =>
                $validate->errorMessage,
            );
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function deleteCatalogueRedis($id)
    {

        $catalogueDelete = true;
        $redis = app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_' . $id);
        if ($exist) {
            $redis->del('vende_catalogue_' . $id);
        }

        return true;
    }

    private function disabledCatalogueProducts($id)
    {
        $data['estado'] = 0;
        $this->productRepository->updateByCatalogueId($id, $data);
    }

    private function deleteBuyers($clientId)
    {
        $this->bblBuyerRepository->destroyByCriteria(["bbl_cliente_id"=>$clientId]);

    }

    private function deleteDiscountCodes($clientId)
    {
        $this->discountCodeRepository->destroyByCriteria(["cliente_id"=>$clientId]);
    }



    private function disableShoppingCarts($id)
    {
        $this->shoppingCartRepository->disableRecords(["estado"=>"abandonado"],["catalogo_id"=>$id],"activo");
    }



}
