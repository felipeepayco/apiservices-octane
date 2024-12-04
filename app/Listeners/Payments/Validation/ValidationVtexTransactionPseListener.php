<?php

namespace App\Listeners\Payments\Validation;

use App\Events\Payments\Validation\ValidationVtexTransactionPseEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\LinkPagoPse;
use Illuminate\Http\Request;

class ValidationVtexTransactionPseListener extends HelperPago
{

    /**
     * ValidationTransactionPseListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    public function handle(ValidationVtexTransactionPseEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;
        $terminal = null;
        $clientId = null;

        ///// clientId ///////////
        if (isset($data['clientId'])) {
            $clientId = $data['clientId'];
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');

            if (!$vclientId) {
                $validate->setError(500, "field clientId required");
            }
        } else {
            $validate->setError(500, "field clientId required");
        }

        ///// terminal ///////////
        if(isset($data['terminal'])){
            $terminal = $data['terminal'];
            $vTerminal = $validate->ValidateVacio($terminal, 'terminal');

            if (!$vTerminal) {
                $validate->setError(500, "field terminal required");
            }
        } else {
            $validate->setError(500, "field terminal required");
        }

        if($terminal && $clientId) {
            $isClientAuthorized = $this->validateLinkClientId($terminal, $clientId);
            if(!$isClientAuthorized){
                $validate->setError(500, "Invalid clientId");
            }
        }

        if ($validate->totalerrors > 0) {
            $data = array('totalErrors' => $validate->totalerrors, 'errors' => $validate->errorMessage);
            $response = array(
                'success' => false,
                'titleResponse' => 'Error',
                'textResponse' => 'Some fields are required, please correct the errors and try again',
                'lastAction' => 'validation data',
                'data' => $data
            );
            $this->saveLog(2, $clientId, '', $response, 'transaction_pse_vtex');

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;
    }

    private function validateLinkClientId(String $terminalId, String $currentClientId)
    {
        try {
            $linkPaymentPse = LinkPagoPse::where("id", $terminalId)->first();

            if($linkPaymentPse){
                return $linkPaymentPse->id_cliente == $currentClientId;
            }
        }
        catch (\Exception $exception) {}

        return false;
    }
}