<?php
namespace App\Service\V2\Category\Validations;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Validation\CommonValidation;

class CreateCategoryValidation extends HelperPago
{

    const EMPTY = 'empty';

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
    public function handle(Request $request)
    {

        try {
            $validate = new Validate();
            $data = $request->all();

            $arr_respuesta = [];
            $clientId = $this->validateIsSet($data, 'clientId', false, '');
            $catalogueId = $this->validateIsSet($data, 'catalogueId', false, '');
            $name = $this->validateIsSet($data, 'name', false, '');
            $logo = $this->validateIsSet($data, 'logo', false);


            if (!CommonValidation::validateBase64Image($logo)) {
                $validate->setError(422, "the logo field is invalid, invalid format");

            }

            $origin = $this->validateIsSet($data, 'origin', "epayco", "string");

            $arr_respuesta["origin"] = isset($data["origin"]) ? $data["origin"] : null;
            if (isset($data["origin"]) && $data["origin"] === 'epayco') {
                $arr_respuesta['logo'] = $logo;
            } else {
                $arr_respuesta['logo'] = null;
            }

            $this->validateParamFormat($arr_respuesta, $validate, $clientId, 'clientId', self::EMPTY);
            $this->validateParamFormat($arr_respuesta, $validate, $catalogueId, 'catalogueId', self::EMPTY);
            $this->validateParamFormat($arr_respuesta, $validate, $name, 'name', 'range', true, [1, 20]);
            $this->validateParamFormat($arr_respuesta, $validate, $name, 'name', self::EMPTY, true);
            $this->validateParamFormat($arr_respuesta, $validate, $logo, 'logo', self::EMPTY, true);
            $this->validateParamFormat($arr_respuesta, $validate, $arr_respuesta["origin"], 'origin', self::EMPTY, true);

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

                $data = array('totalerrors' => $validate->totalerrors,
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

            $has_errors = $validateForbiddenWordService->handle(["nombre" => $name], ["endpoint_action" => isset($data["id"]) ? "actualizar" : "crear", "action" => "Categoría"]);

            if (!$has_errors["success"]) {

                $logService = Container::getInstance()->make(\App\Service\V2\MongoLog\Process\MongoLogService::class);

                $logService->handle(["module" => "Categoría", "action" => "crear", "client_id" => $clientId, "word" => $has_errors["word"]]);

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

    private function validateParamFormat(&$arr_respuesta, $validate, $param, $paramName, $validateType, $required = true, $range = [0, 1])
    {
        if (isset($param)) {
            $vparam = true;

            if ($validateType == self::EMPTY) {
                $vparam = $validate->ValidateVacio($param, $paramName);
            } else if ($validateType == 'string' && $param != "") {
                $vparam = $validate->ValidateStringSize($param, 0, 20);
            } else if ($validateType == 'range') {
                $vparam = $validate->ValidateStringSize($param, $range[0], $range[1]);
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
