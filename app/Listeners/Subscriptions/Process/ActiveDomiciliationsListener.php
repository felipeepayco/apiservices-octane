<?php


namespace App\Listeners\Subscriptions\Process;

use App\Events\Subscriptions\Process\ActiveDomiciliationsEvent;
use App\Exceptions\GeneralException;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Models\SuscripcionClienteSuscripciones;
use App\Service\ResponseHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActiveDomiciliationsListener extends HelperPago
{
    /**
     * ProformaCreateListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ActiveDomiciliationsEvent $event
     * @return array
     */
    public function handle(ActiveDomiciliationsEvent $event): array
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $subscriptionsIds = CommonValidation::getFieldValidation($fieldValidation, "subscriptionsIds", []);

            DB::beginTransaction();
            SuscripcionClienteSuscripciones::whereIn('id', $subscriptionsIds)
                ->where('estado', 'inactivo')
                ->update(['estado' => 'activo']);
            DB::commit();

            return ResponseHandler::generateSuccessResponseDataStructure([],
                "Domiciliaciones actualizadas correctamente",
                "Domiciliaciones actualizadas correctamente",
                "active_domiciliations"
            );

        } catch (GeneralException $generalException) {
            DB::rollBack();

            return ResponseHandler::generateBadResponseDataStructure([],
                "Error al activar las domiciliaciones",
                $generalException->getMessage(),
                "active_domiciliations"
            );
        }

    }
}