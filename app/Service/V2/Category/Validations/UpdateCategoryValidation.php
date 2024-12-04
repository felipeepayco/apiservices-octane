<?php
namespace App\Service\V2\Category\Validations;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CategoryRepository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Validation\CommonValidation;

class UpdateCategoryValidation extends HelperPago
{
    private $category_repository;

    public function __construct(
        Request $request,
        CategoryRepository $category_repository
    ) {

        parent::__construct($request);
        $this->category_repository = $category_repository;

    }

    public function handle($params)
    {

        try {

            $validate = new Validate();
            $data = $params;
            $arr_respuesta = [];

            $clientId = $this->validateIsSet($data, 'clientId', false, 'int');
            $catalogueId = $this->validateIsSet($data, 'catalogueId', false);
            $id = $this->validateIsSet($data, 'id', null);
            $name = $this->validateIsSet($data, 'name', false);

            $origin = $this->validateIsSet($data, 'origin', "epayco", "string");
            $arr_respuesta['origin'] = $origin;

            $logo = $this->validateIsSet($data, 'logo', false);

            if (!CommonValidation::validateBase64Image($logo) && (strlen($logo) > 200 || $id === null)) {
                $validate->setError(422, "the logo field is invalid, invalid format");

            }

            $arr_respuesta['logo'] = $logo;

            $active = $this->validateIsSet($data, 'active', true);
            $arr_respuesta['active'] = $active;

            if (isset($name)) {
                $vname = $validate->ValidateVacio($name, 'name');
                if (!$vname) {
                    $validate->setError(422, "field name required");
                } elseif (strlen($name) < 1) {
                    $validate->setError(422, "field name min 1 characters");
                } elseif (strlen($name) > 20) {
                    $validate->setError(422, "field name max 20 characters");
                } else {
                    $arr_respuesta['name'] = $name;
                }
            } else {
                $validate->setError(422, "field name required");
            }

            $this->validateParamFormat($arr_respuesta, $validate, $catalogueId, 'catalogueId', 'empty', true);
            $this->validateParamFormat($arr_respuesta, $validate, $id, 'id', 'empty');
            $this->validateParamFormat($arr_respuesta, $validate, $clientId, 'clientId', 'empty');
            $this->validateParamFormat($arr_respuesta, $validate, $logo, 'logo', 'empty', true);
            $this->validateParamFormat($arr_respuesta, $validate, $origin, 'origin', 'empty', true);
            $this->validateParamFormat($arr_respuesta, $validate, $id, 'id', 'empty', true);

            if (!$validate->validateIsNumeric($id)) {

                $validate->setError(422, "id field is invalid, numeric value expected");
            } else {
                $id_length = floor(log10(abs($id))) + 1;

                if ($id_length > 20) {

                    $validate->setError(422, "id field can not be greater than 20 digits");

                }

                if ($id < 1) {

                    $validate->setError(422, "id field must be greater than 0");

                }
            }

            if (!$validate->validateIsNumeric($catalogueId)) {

                $validate->setError(422, "catalogueId field is invalid, numeric value expected");
            } else {
                $catalogue_id_length = floor(log10(abs($catalogueId))) + 1;

                if ($catalogue_id_length > 20) {

                    $validate->setError(422, "catalogueId field can not be greater than 20 digits");

                }

                if ($catalogueId < 1) {

                    $validate->setError(422, "catalogueId field must be greater than 0");

                }
            }

            if ($validate->totalerrors > 0) {
                $success = false;
                $last_action = 'validation clientId y data of filter';
                $title_response = 'Error';
                $text_response = 'Some fields are required, please correct the errors and try again';

                $data =
                array('totalerrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage);
                $response = array(
                    'success' => $success,
                    'titleResponse' => $title_response,
                    'textResponse' => $text_response,
                    'lastAction' => $last_action,
                    'data' => $data,
                );

                $this->saveLog(2, $clientId, '', $response, 'consult_catalogue_categories_create');

                return $response;
            }

            //VALIDATE FORBIDDEN WORDS HERE

            $validateForbiddenWordService = Container::getInstance()->make(\App\Service\V2\ForbiddenWord\Process\ValidateForbiddenWordService::class);

            $has_errors = $validateForbiddenWordService->handle(["nombre" => $name], ["endpoint_action" => ($id) ? "actualizar" : "crear", "action" => "Categoría"]);

            if (!$has_errors["success"]) {

                $logService = Container::getInstance()->make(\App\Service\V2\MongoLog\Process\MongoLogService::class);

                $logService->handle(["module" => "Categoría", "action" => "actualizar", "client_id" => $clientId, "word" => $has_errors["word"]]);

                return $has_errors;
            }

            $response = [];
            $response['success'] = true;
            $response['data'] = $arr_respuesta;
            $response['titleResponse'] = "Category is valid";
            $response['textResponse'] = "Category is valid";

            return $response;
        } catch (\Exception $e) {
            Log::info($e);
        }

    }

    private function validateIsSet($data, $key, $default, $cast = "")
    {

        $content = $default;

        if (isset($data[$key])) {
            if ($cast == "int") {
                $content = (int) $data[$key];
            } else if ($cast == "string") {
                $content = (string) $data[$key];
            } else {
                $content = $data[$key];
            }
        }

        return $content;

    }

    private function validateParamFormat(&$arr_respuesta, $validate, $param, $paramName, $validateType, $required = true)
    {
        if (isset($param)) {
            $vparam = true;

            if ($validateType == 'empty') {
                $vparam = $validate->ValidateVacio($param, $paramName);
            } else if ($validateType == 'phone' && $param != "") {
                $vparam = $validate->ValidatePhone($param);
            } else if ($validateType == 'email' && $param != "") {
                $vparam = $validate->ValidateEmail($param, $paramName);
            }

            if (!$vparam) {
                $validate->setError(422, 'field ' . $paramName . ' is invalid');
            } else {
                $arr_respuesta[$paramName] = $param;
            }
        } else {
            if ($required) {
                $validate->setError(422, 'field ' . $paramName . ' required');
            }
        }
    }
}
