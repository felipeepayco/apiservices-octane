<?php

namespace App\Service\V2\Buyer\Process;

use App\Common\TransactionStateCodes;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\BblBuyerRepository;
use App\Repositories\V2\BblPurchaseRepository;

class BuyerListService extends HelperPago
{
    protected BblBuyerRepository $buyerRepository;
    protected BblPurchaseRepository $purchaseRepository;

    public function __construct(BblBuyerRepository $buyerRepository, BblPurchaseRepository $purchaseRepository)
    {
        $this->buyerRepository = $buyerRepository;
        $this->purchaseRepository = $purchaseRepository;

    }
    public function process($params)
    {

        try {
            $fieldValidation = $params;
            $clientId = $fieldValidation["clientId"];
            $buyerId = $fieldValidation["filter"]["id"];

            $pagination = $fieldValidation["pagination"];
            $filters = $fieldValidation["filter"];
            $page = CommonValidation::getFieldValidation((array) $pagination, 'page', 1);
            $pageSize = CommonValidation::getFieldValidation((array) $pagination, 'limit', 50);

            $criteria = ["bbl_comprador.bbl_cliente_id" => (int) $clientId];
            $purchaseData = [];

            if ($buyerId) {
                $criteria = $criteria + ["bbl_comprador.id" => (int) $buyerId];
                $purchaseData = $this->purchaseRepository->getByCriteria(['bbl_comprador_id' => (int) $buyerId]);
            }

            $data = $this->buyerRepository->getByCriteria($criteria);

            $data = (array) collect($data)->toArray();

            if ($buyerId) {

                foreach ($purchaseData as &$p) {
                    switch ($p->status) {
                        case TransactionStateCodes::ACCEPTED:
                            $p->status = "Aceptada";
                            break;
                        case TransactionStateCodes::REJECTED:
                            $p->status = "Rechazada";
                            break;

                        case TransactionStateCodes::PENDING:
                            $p->status = "Pendiente";
                            break;

                        case TransactionStateCodes::FAILED:
                            $p->status = "Fallida";
                            break;

                        case TransactionStateCodes::REVERSED:
                            $p->status = "Reversada";
                            break;

                        case TransactionStateCodes::CANCELLED:
                            $p->status = "Cancelada";
                            break;

                        case TransactionStateCodes::ABANDONED:
                            $p->status = "Abandonada";
                            break;

                        case TransactionStateCodes::EXPIRED:
                            $p->status = "Expirada";
                            break;

                    }
                }

                $data[0]["purchaseData"] = $purchaseData;
            }

            //iniciar paginacion manual
            $totalClients = count($data);
            $totalPages = ceil($totalClients / $pageSize);

            $paginationOffset = $page == 1 ? 0 : ($pageSize * $page) - $pageSize;
            $paginator = array_slice($data, $paginationOffset, $pageSize);

            $newData = [
                "data" => $paginator,
                "currentPage" => $page,
                "from" => $page <= 1 ? 1 : ($page * $pageSize) - ($pageSize - 1),
                "lastPage" => $totalPages,
                "nextPageUrl" => "/buyers?page=" . ($page + 1),
                "path" => "",
                "perPage" => $pageSize,
                "prevPageUrl" => $page <= 2 ? null : "/buyers?page=" . ($page - 1),
                "to" => $page <= 1 ? count($paginator) : ($page * $pageSize) - ($pageSize - 1) + (count($paginator) - 1),
                "total" => $totalClients,
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
