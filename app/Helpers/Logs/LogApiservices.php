<?php

namespace App\Helpers\Logs;

use App\Helpers\Validation\CommonValidation;
use App\Models\GeneralLog;
use Illuminate\Support\Facades\Log;

class LogApiservices
{

    public static function addLog($channel, $request, $response, $project = "general", $customLastAction = "general", $active = true)
    {
        try {

            $clientId = "0000";

            if($project == "social_seller"){
                $clientId = 493891;
            }
            
            $lastAction = isset($response["lastAction"]) ? $response["lastAction"] : $customLastAction;
            $rqst = json_encode($request);
            $rspse = json_encode($response);
            $logText = "ClientId: $clientId";
            $logText .= ", Project: $project";
            $logText .= ", Request: $rqst";
            $logText .= ", Response: $rspse";
            $logText .= ", LastAction: " . $lastAction;
            if (filter_var($active, FILTER_VALIDATE_BOOLEAN)) {
                Log::channel($channel)->info($logText, ['name' => $channel]);
            }
            return true;
        } catch (Exception $ex) {
            //TODO: Optimizar try - catch
            return false;
        }
    }

    public static function setGeneralLog($logInfo){
        $generalLog = new GeneralLog();
        $generalLog->fecha = new \DateTime("now");
        $generalLog->url = CommonValidation::getFieldValidation($logInfo,"url",null);
        $generalLog->accion = CommonValidation::getFieldValidation($logInfo,"action","default");
        $generalLog->mensaje = CommonValidation::getFieldValidation($logInfo,"message","N/A");
        $generalLog->detalle = CommonValidation::getFieldValidation($logInfo,"details",null);
        $generalLog->peticion_externa = CommonValidation::getFieldValidation($logInfo,"externalRequest",null);
        $generalLog->save();
    }

}