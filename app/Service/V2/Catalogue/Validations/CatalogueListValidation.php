<?php

namespace App\Service\V2\Catalogue\Validations;

use App\Http\Validation\Validate;
use Illuminate\Http\Request;
use DateTime;

class CatalogueListValidation
{

    public $response;

    public function validate(Request $request)
    {
        $validate = new Validate();
        $data = $request->request->all();
        $clientId = $this->validateIsSet($data, 'clientId', false, 'int');
        $this->validateParamFormat($clientId, 'int', $validate, 'filter', true);
        $filter = $this->validateIsSet($data, 'filter', false, 'object');
        $this->validateParamFormat($filter, 'object', $validate, 'filter', false);
        $pagination = $this->validateIsSet($data, 'pagination', false, 'object');
        $this->validateParamFormat($pagination, 'object', $validate, 'pagination', false);
        $page = $this->validateIsSet($data, 'page', false, 'object');

        if (isset($data["filter"])) {
            if (isset($data['filter']['id'])) {
                if ($data['filter']['id'] != "") {
                    if (!$validate->validateIsNumeric($data['filter']['id'])) {
                        $validate->setError(422, "field id must be an integer");

                    } else {
                        if ($data['filter']['id'] <= 0) {
                            $validate->setError(422, "field id must be positive and greater than 0");

                        } elseif (strlen((string) $data['filter']['id']) > 20) {
                            $validate->setError(422, "the id field can not be greater than 10 digits ");

                        }
                    }
                }
            }

            if (isset($data["filter"]["initialDate"])) {
                if (!$this->validateDate($data["filter"]["initialDate"])) {
                    $validate->setError(422, "initialDate is invalid yyyy-mm-dd format expected");

                }
            }

            if (isset($data["filter"]["endDate"])) {
                if (!$this->validateDate($data["filter"]["endDate"])) {
                    $validate->setError(422, "endDate is invalid yyyy-mm-dd format expected");

                }
            }
        }

        if (isset($data["pagination"])) {
            if (isset($data["pagination"]["limit"])) {
                if (!$validate->validateIsNumeric($data['pagination']['limit'])) {
                    $validate->setError(422, "the limit field must be an integer ");

                }

                if ($data['pagination']['limit'] <= 0) {
                    $validate->setError(422, "the limit field must be greater than 0 ");

                }

            }

            if (isset($data["pagination"]["page"])) {
                if (!$validate->validateIsNumeric($data['pagination']['page'])) {
                    $validate->setError(422, "the page field must be an integer ");

                }

                if ($data['pagination']['page'] <= 0) {
                    $validate->setError(422, "the page field must be greater than 0 ");

                }
            }
        }

        $arr_respuesta["clientId"] = $clientId;
        $arr_respuesta["filter"] = $filter;
        $arr_respuesta["pagination"] = $pagination;
        $arr_respuesta["page"] = $page;

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

            $this->response = $response;
            return false;
        }

        $arr_respuesta['success'] = true;
        $this->response = $arr_respuesta;
        return true;
    }
    private function validateParamFormat($validated, $validateType, &$validate, $paramName, $required = true)
    {

        if ($required && !isset($validated)) {
            $validate->setError(500, 'field ' . $paramName . ' required');
        }

        if (isset($validated) && $paramName === 'filter') {
            $vparam = false;
            gettype($validated) === $validateType ? $vparam = true : $vparam = false;

            return $vparam;
        }

        if (isset($validated) && $paramName === 'clientId') {
            $vparam = false;
            gettype($validated) === $validateType ? $vparam = true : $vparam = false;

            return $vparam;
        }
    }
    public static function validateIsSet($data, $key, $default, $cast = "")
    {

        $content = $default;

        if (isset($data[$key])) {
            switch ($cast) {
                case "int":
                    $content = (int) $data[$key];
                    break;
                case "string":
                    $content = (string) $data[$key];
                    break;
                case "date":
                    $content = date("Y-m-d H:i:s", strtotime($data[$key]));
                    break;
                default:
                    $content = $data[$key];
                    break;
            }
        }

        return $content;
    }

    private function validateDate($date)
    {
        // Check format using regex
        $regex = '/^\d{4}-\d{2}-\d{2}$/';
        if (preg_match($regex, $date) !== 1) {
            return false;
        }

        // Validate date using DateTime
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
