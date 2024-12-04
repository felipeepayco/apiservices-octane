<?php

namespace App\Http\Middleware;

use App\Events\Payments\Validation\ValidationVtexTransactionPseEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Closure;
use Illuminate\Http\Request;

class VtexPseMiddleware extends HelperPago
{
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle($request, Closure $next)
    {
        try {
            $arrParams = $request->request->all();
            $validationVtexTransactionPse = event(new ValidationVtexTransactionPseEvent($arrParams), $request);

            if(!isset($validationVtexTransactionPse[0]["success"])) return response('Unauthorized.', 401);

            if (!$validationVtexTransactionPse[0]["success"]) {
                return $this->crearRespuesta($validationVtexTransactionPse[0]);
            }

            return $next($request);
        } catch (\Exception $exception) {
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );

            $arrResponse['success'] = false;
            $arrResponse['titleResponse'] = "Error code: " . $exception->getCode();
            $arrResponse['textResponse'] = "Error internal server: " . $exception->getMessage();
            $arrResponse['lastAction'] = "NA";
            $arrResponse['data'] = $data;

            return $arrResponse;
        }
    }
}
