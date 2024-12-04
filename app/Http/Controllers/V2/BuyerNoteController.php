<?php

namespace App\Http\Controllers\V2;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use App\Service\V2\Note\Process\ListNoteService;
use App\Service\V2\Note\Process\CreateNoteService;
use App\Service\V2\Note\Process\DeleteNoteService;
use App\Service\V2\Note\Validations\ListNoteValidation;
use App\Service\V2\Note\Validations\CreateNoteValidation;
use App\Service\V2\Note\Validations\DeleteNoteValidation;
use Illuminate\Http\Request;

class BuyerNoteController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function index(Request $request, ListNoteService $list_note_service, ListNoteValidation $list_note_validation)
    {
        try {

            $list_note_validation = $list_note_validation->validate($request);
            if (!$list_note_validation["success"]) {

                return $this->crearRespuesta($list_note_validation);
            }

            $note_list = $list_note_service->process($list_note_validation);

            $success = $note_list['success'];
            $title_response = $note_list['titleResponse'];
            $text_response = $note_list['textResponse'];
            $last_action = $note_list['lastAction'];
            $data = $note_list['data'];
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

    public function create(Request $request, CreateNoteValidation $validation, CreateNoteService $service)
    {
        try {

            if (!$validation->validation($request)) {
                return $this->responseSpeed($validation->response);
            }

            $process = $service->process($validation->response);

            $success = $process['success'];
            $title_response = "Service Note Save";
            $text_response = $process['msg'];
            $last_action = "Create Note";
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

    public function delete(Request $request, DeleteNoteValidation $validation, DeleteNoteService $service)
    {
        try {

            if (!$validation->validation($request)) {
                return $this->responseSpeed($validation->response);
            }

            $process = $service->process($validation->response);

            $success = $process['success'];
            $title_response = "Service Note Delete";
            $text_response = $process['msg'];
            $last_action = "Delete Note";
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
