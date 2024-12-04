<?php

namespace App\Http\Middleware;

use App\Models\BblClientes;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Clientes;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\DetalleConfClientes;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Config;

class JwtMiddleware
{
    use ApiResponser;
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    public $request;
    public const ACTIVE = 1;

    /**
     * Create a new middleware instance.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     * @return void
     */
    public function __construct(Auth $auth, Request $request)
    {
        $this->auth = $auth;
        $this->request = $request;
    }

    public function bearerToken()
    {
        $header = $this->request->header('Authorization', '');
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {

            $token = $this->bearerToken();

            $decoded = JWT::decode($token, new Key(Config::get('app.jwt_secret'), 'HS256'));
            $decoded_array = (array)$decoded;
            $clienteId = $decoded_array['sub'];
            $expired = $decoded_array['exp'];
            $restricted = $decoded_array['res'];
            $grantUserId = $decoded_array['gui'];

            $request->request->add(['clientId' => $clienteId]);
            $request->request->add(['grantUserId' => $grantUserId]);

            if ( $clienteId ) {
                $cliente = BblClientes::find($clienteId);
                if ($cliente) {
                    app('translator')->setLocale(strtolower("ES"));
                } else {
                    $token = "";
                }
            }

            if (strtotime(date('Y-m-d H:i:s')) > $expired) {
                $token = "";
            }
        } catch (\Throwable $ex) {
            \Log::info($ex);
            $token = "";
            // ayuda a ver el error dd($ex->getMessage());

            if( $token == "") {
                return $this->defaultApiResponse(false, 'Unauthorized.', 'Unauthorized.','',$ex, 401);
            }
        }

       
            
        return $next($request);
    }

}
