<?php

namespace App\Service\V2\Note\Process;

use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\BblNoteRepository;

class ListNoteService extends HelperPago
{
    protected BblNoteRepository $bblNoteRepository;

    public function __construct(BblNoteRepository $bblNoteRepository)
    {
        $this->bblNoteRepository = $bblNoteRepository;

    }
    public function process($params)
    {

        try {
            $fieldValidation = $params;
            $clientId = $fieldValidation["clientId"];
            $buyerId = $fieldValidation["filter"]["buyerId"];
            $noteId = $fieldValidation["filter"]["id"];

            $pagination = $fieldValidation["pagination"];
            $filters = $fieldValidation["filter"];
            $page = CommonValidation::getFieldValidation((array) $pagination, 'page', 1);
            $pageSize = CommonValidation::getFieldValidation((array) $pagination, 'limit', 50);

            $criteria = ["bbl_notas.bbl_comprador_id" => (int) $buyerId];

            if ($noteId) {
                $criteria = $criteria + ["bbl_notas.id" => (int) $noteId];
            }

            $data = $this->bblNoteRepository->getByCriteria($criteria);
            $data = $data->toArray();

            //iniciar paginacion manual
            $totalRecords = count($data);
            $totalPages = ceil($totalRecords / $pageSize);

            $paginationOffset = $page == 1 ? 0 : ($pageSize * $page) - $pageSize;
            $paginator = array_slice($data, $paginationOffset, $pageSize);

            $newData = [
                "data" => $paginator,
                "currentPage" => $page,
                "from" => $page <= 1 ? 1 : ($page * $pageSize) - ($pageSize - 1),
                "lastPage" => $totalPages,
                "nextPageUrl" => "/buyers-notes/list?page=" . ($page + 1),
                "path" => "",
                "perPage" => $pageSize,
                "prevPageUrl" => $page <= 2 ? null : "/buyers-notes/list?page=" . ($page - 1),
                "to" => $page <= 1 ? count($paginator) : ($page * $pageSize) - ($pageSize - 1) + (count($paginator) - 1),
                "total" => $totalRecords,
            ];

            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $newData;

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error ' . $exception->getMessage();
            $text_response = "Error query to database " . $exception->getLine();
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

}
