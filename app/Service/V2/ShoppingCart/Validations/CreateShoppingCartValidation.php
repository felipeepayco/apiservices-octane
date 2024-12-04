<?php
namespace App\Service\V2\ShoppingCart\Validations;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Helpers\Validation\CommonValidation;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use App\Helpers\Messages\CommonText;

class CreateShoppingCartValidation extends HelperPago
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

        $operator = CommonValidation::validateIsSet($data, 'operator', "", 'string');
        $arrRespuesta['operator'] = $operator;

        $valueDelivery = CommonValidation::validateIsSet($data, 'valueDelivery', 0, 'number');
        $arrRespuesta['valueDelivery'] = $valueDelivery;

        $quantity = CommonValidation::validateIsSet($data, 'quantity', 0, 'number');
        $arrRespuesta['quantity'] = $quantity;

        $ip = CommonValidation::validateIsSet($data, 'ip', null, 'string');
        $arrRespuesta['ip'] = $ip;

        CommonValidation::validateParamFormat($arrRespuesta, $validate, $clientId, 'clientId', CommonText::EMPTY);
        CommonValidation::validateParamFormat($arrRespuesta, $validate, $productId, 'productId', CommonText::EMPTY);
        CommonValidation::validateParamFormat($arrRespuesta, $validate, $quantity, 'quantity', "int");

        if ($quantity <= 0) {
            $validate->setError(422, "quantity invalid");
        }

        $criteria = [
            'cliente_id' => $clientId,
            'id' => $productId,
        ];
        $product = $this->productRepository->getByCriteria($criteria);
        $product = $product && count($product) > 0 ? $product[0] : null;
        $arrRespuesta['product'] = $product;

        if (!$product) {
            $validate->setError(422, "product not found");
        } else if (count($product->referencias)) {
            $arrRespuesta['catalogueId'] = $product->catalogo_id;
            $referenceId = CommonValidation::validateIsSet($data, 'referenceId', null);
            CommonValidation::validateParamFormat($arrRespuesta, $validate, $referenceId, 'referenceId', CommonText::EMPTY);
            $arrRespuesta['referenceId'] = $referenceId;
            $validateReferenceId = false;
            foreach ($product->referencias as $key => $ref) {
                if ($ref["id"] === $referenceId) {
                    $validateReferenceId = true;
                    if ($ref["disponible"] < $quantity) {
                        $validate->setError(422, "with insufficient stock");
                    } else {
                        $arrRespuesta['refData'] = $ref;
                        $arrRespuesta['indexRefProduct'] = $key;
                    }
                }
            }

            if (!$validateReferenceId) {
                $validate->setError(422, "reference not found");
            }
        } else {
            $arrRespuesta['catalogueId'] = $product->catalogo_id;
            if ($product->disponible < $quantity) {
                $validate->setError(422, "with insufficient stock");
            }
        }

        if ($id) {
            $shoppingCart = $this->shoppingCartRepository->findByIdAndClient($id, $clientId);
            $arrRespuesta['shoppingCart'] = $shoppingCart;
            if (!$shoppingCart) {
                $validate->setError(422, "shoppingCart not found");
            }
        }




        if ($validate->totalerrors > 0) {

            $success = false;
            $last_action = 'validation clientId y data of filter';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data =
            array(
                'totalerrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage,
            );
            $response = array(
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            );
            return $response;
        }

        $arrRespuesta['success'] = true;

        return $arrRespuesta;
    }
}
