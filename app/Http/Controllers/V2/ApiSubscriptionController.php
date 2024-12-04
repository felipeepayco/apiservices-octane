<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Service\V2\Subscription\Process\DisableNotificationService;
use App\Service\V2\Subscription\Process\ListInvoicesService;
use App\Service\V2\Subscription\Validations\DisableNotificationValidation;
use App\Service\V2\Subscription\Validations\ListInvoicesValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiSubscriptionController extends Controller
{

    public function disableSubscription(Request $request, DisableNotificationValidation $disableNotificationValidation, DisableNotificationService $disableNotificationService)
    {
        try {

            $disableNotificationsvalidationResponse = $disableNotificationValidation->validation($request);
            if (!$disableNotificationsvalidationResponse["success"]) {
                return $this->crearRespuesta($disableNotificationsvalidationResponse);
            }

            $disableNotificationsResponse = $disableNotificationService->process($disableNotificationsvalidationResponse["data"]);

            $success = $disableNotificationsResponse['success'];
            $titleResponse = $disableNotificationsResponse['titleResponse'];
            $textResponse = $disableNotificationsResponse['textResponse'];
            $lastAction = $disableNotificationsResponse['lastAction'];
            $data = $disableNotificationsResponse['data'];
        } catch (\Exception $exception) {

            Log::info($exception);
            $success = false;
            $titleResponse = "Error";
            $textResponse = "Error when querying" . $exception->getMessage();
            $lastAction = "NA";
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
            'titleResponse' => $titleResponse,
            'textResponse' => $textResponse,
            'lastAction' => $lastAction,
            'data' => $data,
        );
        return $this->crearRespuesta($response);
    }

    public function listInvoices(Request $request, ListInvoicesValidation $listInvoicesValidation, ListInvoicesService $listInvoicesService)
    {

        try {
            if (!$listInvoicesValidation->validate($request)) {

                return $this->responseSpeed($listInvoicesValidation->response);

            }
            $consultCatalogueList = $listInvoicesService->process($listInvoicesValidation->response);

            $success = $consultCatalogueList['success'];
            $title_response = "Servicio detalle de facturacion";
            $text_response = $consultCatalogueList['textResponse'];
            $last_action = "NA";
            $data = $consultCatalogueList['data'];

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
            'data' => $data['data'] ?? [],
            'pagination' => $data['pagination'] ?? [],
        ];
        return $this->responseSpeed($response);
    }
}
