<?php

namespace App\Http\Middleware;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\Transacciones;
use Closure;
use Illuminate\Http\Request;

class VtexReturnPseMiddleware extends HelperPago
{
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle($request, Closure $next)
    {
        try {
            $invoiceId = $request->route()[2]['invoiceId'];
            
            if($invoiceId != ""){
                $tr = Transacciones::where('id_factura', '=', $invoiceId)->first();
                
                if(is_object($tr)){
                    $request->request->add(['transactionID' => $tr->recibo]);
                    $request->request->add(['clientId' => $tr->id_cliente]);
                    $request->request->add(['confirmUrl' => $tr->urlconfirmacion]);
                }
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
