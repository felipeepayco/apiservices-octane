<?php


namespace App\Listeners\Catalogue\Process\Plans;


use App\Events\Catalogue\Process\Plans\DowngradePlanEvent;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Listeners\Services\VendeConfigPlanService;
use App\Models\Productos;
use App\Helpers\Messages\CommonText as CM;

class DowngradePlanListener extends HelperPago
{
    public function handle(DowngradePlanEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;

            $productId = $fieldValidation["productId"];
            $clientIdentifier = $fieldValidation["clientIdentifier"];
            $vendeConfigPlanService = new VendeConfigPlanService();

            $planConfig = $vendeConfigPlanService->getPlanConfig($productId);

            // Inactivar catalogos y productos que el usuario tenga activos sobre las restricciones del plan
            $currentCatalogs = $vendeConfigPlanService->getTotalActiveCatalogs($clientIdentifier,CM::ORIGIN_EPAYCO, null, false, true);
            $groupCatalogsDowngrade = $this->groupCatalogsDowngrade($planConfig,$currentCatalogs);
            $vendeConfigPlanService->disableCatalogsOverCurrentPlan($groupCatalogsDowngrade["disabled"]);
            $vendeConfigPlanService->enabledCataloguePlanStatus($groupCatalogsDowngrade[CommonText::ENABLED_ENG]);

            // Inactivar productos que el usuario tenga activos sobre las restricciones del plan
            $currentProducts = $vendeConfigPlanService->getTotalActiveProducts($groupCatalogsDowngrade[CommonText::ENABLED_ENG],CM::ORIGIN_EPAYCO);
            $groupProductDowngrade = $this->groupProductsDowngrade($planConfig["allowedProducts"],$currentProducts);
            $vendeConfigPlanService->disableProductsOverCurrentPlan($groupProductDowngrade["disabled"]);

            $vendeConfigPlanService->disableAnalitics($clientIdentifier,$planConfig["allowedAnalitics"]);            

            $success = true;
            $title_response = 'Apply restrictions';
            $text_response = "Apply restrictions";
            $last_action = 'Apply restrictions';
            $data = [];

        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error '.$exception->getMessage();
            $text_response = "Error create new catalogue";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function groupCatalogsDowngrade($planConfig,$currentCatalogs){

        $countCurrentCatalogs = count($currentCatalogs);

        $groupDowngradeCatalogs = [
            CommonText::ENABLED_ENG=>$currentCatalogs,
            "disabled"=>[]
        ];

        $allowedCatalogs = $planConfig["allowedCatalogs"];

        if($allowedCatalogs != "ilimitado" && $countCurrentCatalogs>intval($allowedCatalogs)){
            $enabledCatalogs = array_slice($currentCatalogs,0,intval($allowedCatalogs));
            $disableCatalogs = array_slice($currentCatalogs,intval($allowedCatalogs));

            $groupDowngradeCatalogs = [
                CommonText::ENABLED_ENG=>$enabledCatalogs,
                "disabled"=>$disableCatalogs
            ];

        }

        return $groupDowngradeCatalogs;

    }

    private function groupProductsDowngrade($allowedProducts,$currentProducts){

        $countCurrentProducts = count($currentProducts);

        $groupDowngradeProducts = [
            CommonText::ENABLED_ENG=>$currentProducts,
            "disabled"=>[]
        ];


        if($allowedProducts != "ilimitado" && $countCurrentProducts>intval($allowedProducts)){
            $enabledProducts = array_slice($currentProducts,0,intval($allowedProducts));
            $disableProducts = array_slice($currentProducts,intval($allowedProducts));

            $groupDowngradeProducts = [
                CommonText::ENABLED_ENG=>$enabledProducts,
                "disabled"=>$disableProducts
            ];

        }

        return $groupDowngradeProducts;

    }


}
