<?php


namespace App\Listeners\RestrictiveList\Process;


use App\Common\RiskTypeClient;
use App\Events\RestrictiveList\Process\ProcessRestrictiveListSaveLogEvent;
use App\Helpers\ClientRegister\HelperClientRegister;
use App\Models\ClientsRestrictiveList;
use Carbon\Carbon;
use DateTime;
use Exception;

class ProcessRestrictiveListSaveLogListener extends HelperClientRegister
{
    /**
     * @param ProcessRestrictiveListSaveLogEvent $event
     * @return array
     */
    public function handle(ProcessRestrictiveListSaveLogEvent $event)
    {
        $preRegister = $event->preRegister;
        $request = $event->request;
        $isClientInList = $event->isClientInLists;
        $serviceResponse = $event->serviceResponse;
        $validationType = $event->validationType;

        try {
            $logClientList = new ClientsRestrictiveList();

            if ($preRegister) {
                $logClientList->id_pre_registro = $preRegister->id;
                $logClientList->id_entidad_aliada = $preRegister->id_cliente_entidad_aliada;
                $logClientList->id_cliente = $preRegister->cliente_id;
            }

            $logClientList->fecha_creacion = new DateTime('now');
            $logClientList->numero_documento = $request['docNumber'];
            $logClientList->tipo_doc = $request['docType'];
            $logClientList->digito = $request['digit'];
            $logClientList->request_service = json_encode($request);
            $logClientList->response_service = $serviceResponse ? json_encode($serviceResponse) : null;
            $logClientList->usuario_lista = $isClientInList;
            $logClientList->id_tipo_validacion = $validationType;
            $logClientList->id_tipo_riesgo = $isClientInList === true ? RiskTypeClient::NOT_OBJECTIVE : RiskTypeClient::OBJECTIVE;
            $logClientList->estado_reportado = $isClientInList;

            $logClientList->save();

        } catch (Exception $exception) {
        }
    }
}
