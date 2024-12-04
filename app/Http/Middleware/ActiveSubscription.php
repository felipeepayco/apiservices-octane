<?php

namespace App\Http\Middleware;

use App\Helpers\Validation\CommonValidation;
use Closure;

class ActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $sub = CommonValidation::validateActiveSubscription($request->clientId);
        if (!$sub) {

            $response = array(
                'success' => false,
                'titleResponse' => "unauthorized",
                'textResponse' => "El comercio no cuenta con suscripción de Shops",
                'lastAction' => "unauthorized",
                'data' => []

            );
            return response()->json($response, 401);
        }
        return $next($request);
    }
}
