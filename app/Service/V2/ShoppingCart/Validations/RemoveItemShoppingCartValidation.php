<?php
namespace App\Service\V2\ShoppingCart\Validations;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Helpers\Validation\CommonValidation;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use App\Helpers\Messages\CommonText;

class RemoveItemShoppingCartValidation extends HelperPago
{
    private $productRepository;
    private $shoppingCartRepository;

    public function __construct(Request $request, ProductRepository $productRepository, ShoppingCartRepository $shoppingCartRepository)
    {
        $this->productRepository = $productRepository;
        $this->shoppingCartRepository = $shoppingCartRepository;

        parent::__construct($request);
    }

    public function handle(Request $request)
    {

        $validate = new Validate();
        $data = $request->all();

        $clientId = CommonValidation::validateIsSet($data, 'clientId', null, 'int');
        $arrRespuesta['clientId'] = $clientId;


        $productId = CommonValidation::validateIsSet($data, 'productId', null, 'int');
        $arrRespuesta['productId'] = $productId;


        $id = CommonValidation::validateIsSet($data, 'id', null, 'string');
        $arrRespuesta['id'] = $id;

        CommonValidation::validateParamFormat($arrRespuesta, $validate, $clientId, 'clientId', CommonText::EMPTY);
        CommonValidation::validateParamFormat($arrRespuesta, $validate, $id, 'id', CommonText::EMPTY);
        CommonValidation::validateParamFormat($arrRespuesta, $validate, $productId, 'productId', CommonText::EMPTY);


        $product = $this->productRepository->find($productId);
        $arrRespuesta['product'] = $product;
        if (!$product) {
            $validate->setError(422, "product not found");
        } else if (count($product->referencias)) {
            $referenceId = CommonValidation::validateIsSet($data, 'referenceId', null);
            CommonValidation::validateParamFormat($arrRespuesta, $validate, $referenceId, 'referenceId', CommonText::EMPTY);
            $arrRespuesta['referenceId'] = $referenceId;
            $validateReferenceId = false;
            foreach ($product->referencias as $ref) {
                if ($ref["id"] === $referenceId) {
                    $validateReferenceId = true;
                }
            }

            if (!$validateReferenceId) {
                $validate->setError(422, "reference not found");
            }
        }



        $shoppingCart = $this->shoppingCartRepository->findByIdAndClient($id, $clientId);
        $arrRespuesta['shoppingCart'] = $shoppingCart;
        if (!$shoppingCart) {
            $validate->setError(422, "shoppingCart not found");
        } else {
            $productCartValidate = false;
            foreach ($shoppingCart->productos as $productDataCart) {
                if ($productDataCart["id"] === $productId) {
                    $productCartValidate = true;
                    break;
                }
            }
            if (!$productCartValidate) {
                $validate->setError(422, "product not found in shoppingCart");
            }
        }

        if ($validate->totalerrors > 0) {

            $success = false;
            $last_action = 'validation id ';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
            array(
                'totalerrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            );
            return array(
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            );
        }

        $arrRespuesta['success'] = true;
        return $arrRespuesta;


    }
}
