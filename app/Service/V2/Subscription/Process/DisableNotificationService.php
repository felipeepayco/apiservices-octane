<?php
namespace App\Service\V2\Subscription\Process;

use App\Repositories\V2\BblSubscriptionRepository;
use Illuminate\Support\Facades\Log;

class DisableNotificationService
{
    private $bblSubscriptionRepository;

    public function __construct(BblSubscriptionRepository $bblSubscriptionRepository)
    {
        $this->bblSubscriptionRepository = $bblSubscriptionRepository;
    }

    public function process($fieldValidation)
    {

        try {
            $data = $this->bblSubscriptionRepository->update(["notificacion" => true], ["id" => $fieldValidation["subscriptionId"]]);

            $success = true;
            $titleResponse = "Success";
            $textResponse = 'Suscription updated successfully';
            $lastAction = "disableNotification";

        } catch (\Exception $exception) {
            Log::info($exception);
            $success = false;
            $titleResponse = "Error";
            $textResponse = "Error when querying" . $exception->getMessage();
            $lastAction = "NA";
            $data = array(
                'totalErrors' => 1,
                'errors' => $exception->getMessage(),
            );
        }

        return [
            'success' => $success,
            'titleResponse' => $titleResponse,
            'textResponse' => $textResponse,
            'lastAction' => $lastAction,
            'data' => $data,
        ];

    }
}
