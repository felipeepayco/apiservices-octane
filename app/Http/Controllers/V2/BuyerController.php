<?php

namespace App\Http\Controllers\V2;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use App\Service\V2\Buyer\Process\BuyerListService;
use App\Service\V2\Buyer\Process\CreateBuyerService;
use App\Service\V2\Buyer\Process\DeleteBuyerService;
use App\Service\V2\Buyer\Process\UpdateBuyerService;
use App\Service\V2\Buyer\Validations\BuyerListValidation;
use App\Service\V2\Buyer\Validations\CreateBuyerValidation;
use App\Service\V2\Buyer\Validations\DeleteBuyerValidation;
use App\Service\V2\Buyer\Validations\UpdateBuyerValidation;
use Illuminate\Http\Request;

class BuyerController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function getBuyers(Request $request, BuyerListService $buyer_list_service, BuyerListValidation $buyer_list_validation)
    {
        try {

            $buyer_list_validation = $buyer_list_validation->validate($request);
            if (!$buyer_list_validation["success"]) {
                return $this->crearRespuesta($buyer_list_validation);
            }

            $buyer_list = $buyer_list_service->process($buyer_list_validation);

            $success = $buyer_list['success'];
            $title_response = $buyer_list['titleResponse'];
            $text_response = $buyer_list['textResponse'];
            $last_action = $buyer_list['lastAction'];
            $data = $buyer_list['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error";
            $text_response = "Error inesperado al consultar la informacion" . $exception->getMessage();
            $last_action = "NA";
            $error = (object) $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' => $validate->errorMessage,
            );
        }

        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data,
        );
        return $this->crearRespuesta($response);

    }

    public function create(Request $request, CreateBuyerValidation $validation, CreateBuyerService $service)
    {
        try {

            if (!$validation->validation($request)) {
                return $this->responseSpeed($validation->response);
            }

            $process = $service->process($validation->response);

            $success = $process['success'];
            $title_response = "Service Buyer Save";
            $text_response = $process['msg'];
            $last_action = "Create Buyer";
            $data = $process['data'];

        } catch (\Exception $e) {

            $success = false;
            $title_response = "Error";
            $text_response = $this->getErrorDetail($e);
            $last_action = "NA";
            $data = [];

        }
        $response = [
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data ?? [],
        ];
        return $this->responseSpeed($response);
    }

    public function update(Request $request, UpdateBuyerValidation $validation, UpdateBuyerService $service)
    {
        try {

            if (!$validation->validation($request)) {
                return $this->responseSpeed($validation->response);
            }

            $process = $service->process($validation->response);

            $success = $process['success'];
            $title_response = "Service Buyer Update";
            $text_response = $process['msg'];
            $last_action = "Update Buyer";
            $data = $process['data'];

        } catch (\Exception $e) {

            $success = false;
            $title_response = "Error";
            $text_response = $this->getErrorDetail($e);
            $last_action = "NA";
            $data = [];

        }
        $response = [
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data ?? [],
        ];
        return $this->responseSpeed($response);
    }

    public function delete(Request $request, DeleteBuyerValidation $validation, DeleteBuyerService $service)
    {
        try {

            if (!$validation->validation($request)) {
                return $this->responseSpeed($validation->response);
            }

            $process = $service->process($validation->response);

            $success = $process['success'];
            $title_response = "Service Buyer Delete";
            $text_response = $process['msg'];
            $last_action = "Delete Buyer";
            $data = $process['data'];

        } catch (\Exception $e) {

            $success = false;
            $title_response = "Error";
            $text_response = $this->getErrorDetail($e);
            $last_action = "NA";
            $data = [];

        }
        $response = [
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data ?? [],
        ];
        return $this->responseSpeed($response);
    }
}
