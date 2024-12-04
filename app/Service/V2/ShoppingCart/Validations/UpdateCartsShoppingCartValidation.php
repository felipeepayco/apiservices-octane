<?php
namespace App\Service\V2\ShoppingCart\Validations;

use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateCartsShoppingCartValidation extends HelperPago
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
        $arr_respuesta['clientId'] = $clientId;

        $shoppingCartId = CommonValidation::validateIsSet($data, 'shoppingCartId', null, 'string');
        $arr_respuesta['id'] = $shoppingCartId;

        if (!isset($data["products"])) {
            $validate->setError(422, "products property is required");

            return $this->returnError($validate);
        }

        CommonValidation::validateParamFormat($arr_respuesta, $validate, $clientId, 'clientId', CommonText::EMPTY);
        CommonValidation::validateParamFormat($arr_respuesta, $validate, $shoppingCartId, 'shoppingCartId', CommonText::EMPTY);

        foreach ($data["products"] as $pr) {

            if (!isset($pr["id"])) {
                $validate->setError(422, "product id property is required");
            }

            if (!isset($pr["quantity"])) {
                $validate->setError(422, "product quantity property is required");
            } else {
                if (!$validate->validateIsNumeric($pr["quantity"])) {
                    $validate->setError(422, "product quantity must be numeric");

                }

                if ($pr["quantity"] < 1) {

                    $validate->setError(422, "product quantity must be greater than or equal to 1");

                }
            }

            //VALIDATE ONLY IF PRODUCTS DON'T HAVE REFERENCES
            if (!isset($pr["references"]))
            {
                if (!isset($pr["action"])) {
                    $validate->setError(422, "product action property is required");
                } else {
                    if ($pr["action"] != "substract" && $pr["action"] != "add") {
                        $validate->setError(422, "product action value must be either 'substract' or 'add'");
                    }
                }
    
                if (!isset($pr["finalQuantity"])) {
                    $validate->setError(422, "product finalQuantity property is required");
                } else {
                    if (!$validate->validateIsNumeric($pr["finalQuantity"])) {
                        $validate->setError(422, "product finalQuantity must be numeric");
    
                    }

                }
    
                if (!isset($pr["initialQuantity"])) {
                    $validate->setError(422, "product initialQuantity property is required");
                } else {
                    if (!$validate->validateIsNumeric($pr["initialQuantity"])) {
                        $validate->setError(422, "product initialQuantity must be numeric");
    
                    }
    
                    if ($pr["initialQuantity"] < 1) {
    
                        $validate->setError(422, "product initialQuantity must be greater than or equal to 1");
    
                    }
                }
            }
         
            $hasErrors = $this->returnError($validate);

            if (!$hasErrors["success"]) {
                return $hasErrors;
            }

            $product = $this->productRepository->find($pr["id"]);

            if (!$product) {
                $validate->setError(422, "product not found");
            } else if (isset($pr["references"]) && count($pr["references"])) {

                $productReferences = collect($product->referencias)->pluck("id")->all();
                foreach ($pr["references"] as $ref) {

                    if (!isset($ref["id"])) {
                        $validate->setError(422, "reference id property is required");
                    }

                    if (!isset($ref["quantity"])) {
                        $validate->setError(422, "reference quantity property is required");
                    } else {
                        if (!$validate->validateIsNumeric($ref["quantity"])) {
                            $validate->setError(422, "reference quantity must be numeric");

                        }

                        if ($ref["initialQuantity"] < 1) {

                            $validate->setError(422, "reference quantity must be greater than or equal to 1");

                        }
                    }

                    if (!isset($ref["action"])) {
                        $validate->setError(422, "reference action property is required");
                    } else {

                        if ($ref["action"] != "substract" && $ref["action"] != "add") {
                            $validate->setError(422, "reference action value must be either 'substract' or 'add'");
                        }
                    }

                    if (!isset($ref["finalQuantity"])) {
                        $validate->setError(422, "reference finalQuantity property is required");
                    } else {
                        if (!$validate->validateIsNumeric($ref["finalQuantity"])) {
                            $validate->setError(422, "reference finalQuantity must be numeric");

                        }

                    }

                    if (!isset($ref["initialQuantity"])) {
                        $validate->setError(422, "reference initialQuantity property is required");
                    } else {
                        if (!$validate->validateIsNumeric($ref["initialQuantity"])) {
                            $validate->setError(422, "reference initialQuantity must be numeric");

                        }

                        if ($ref["initialQuantity"] < 1) {

                            $validate->setError(422, "reference initialQuantity must be greater than or equal to 1");

                        }
                    }

                    $hasErrors = $this->returnError($validate);

                    if (!$hasErrors["success"]) {
                        return $hasErrors;
                    }

                    if (in_array($ref["id"], $productReferences)) {
                        $validateReferenceId = true;
                    }
                }

                if (!$validateReferenceId) {
                    $validate->setError(422, "reference not found");
                }
            }

        }

        $shoppingCart = $this->shoppingCartRepository->findByIdAndClient($shoppingCartId, $clientId);

        if (!$shoppingCart) {
            $validate->setError(422, "shoppingCart not found");
        }

        $hasErrors = $this->returnError($validate);

        if (!$hasErrors["success"]) {
            return $hasErrors;
        }
        $arr_respuesta['success'] = true;
        return $arr_respuesta;

    }

    private function returnError($validate)
    {
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

        return array(
            'success' => true,
        );
    }
}
