<?php

namespace App\Http\Controllers;

use App\Http\Lib\DescryptObject;
use App\Models\Clientes;
use App\Models\ApifyClientes;
use App\Models\ProductosClientes;
use App\Models\GrantUserOauth;
use App\Models\PasarelaConfig;
use App\Models\LlavesClientes;
use App\Models\GrantUser;
use App\Models\BblClientesPasarelas;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use App\Models\BblClientes;
use App\Models\DetalleConfClientes;
use Illuminate\Support\Facades\DB;
use WpOrg\Requests\Requests;
use Illuminate\Support\Facades\Config;

class AuthController extends HelperPago
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new controller instance.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Create a new token.
     *
     * @param BblClientesPasarelas $llaves
     * @return string
     */
    protected function jwt(BblClientesPasarelas $llaves , $grantUserId = null)
    {
        $cliente_id = $llaves->cliente_id;
        
        $restricted = false;
        $inactive = false;
        // $client = Clientes::find($cliente_id);
        // if ( $client ) {
        //     $restricted = ( bool ) $client->restricted_user;
        //     $inactive = $client->id_estado != 1;
        // }

        $payload = [
            'iss' => "apifyePaycoJWT", // Issuer of the token
            'sub' => $cliente_id, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + 60 * 60, // Expiration time
            'rand' => md5(microtime()) . rand(0, 10000),
            'res' => $restricted, // user is restricted or not
            'ina' => $inactive, // user inactive by estado_id
            'gui' => $grantUserId
        ];


        // As you can see we are passing `JWT_SECRET` as the second parameter that will
        // be used to decode the token in the future.
        return JWT::encode($payload, Config::get('app.jwt_secret'),'HS256');
    }
    public function loginAutomaticByEpayco(Request $request, $jwt)
    {
        try {
            $codeJwt=Config::get('app.jwt_secret');
            $decoded = JWT::decode($jwt, $codeJwt, array('HS256'));
            $bblClientes= BblClientes::find($decoded->data->id);
            if(!$bblClientes){
                $BblClientes= new BblClientes();
                $BblClientes->id=$decoded->data->id;
                $BblClientes->email=$decoded->data->email;
                $BblClientes->nombre=$decoded->data->nombre ?? "";
                $BblClientes->apellido=$decoded->data->apellido ?? "";
                $BblClientes->documento=$decoded->data->documento;
                $BblClientes->tipo_doc=$decoded->data->tipo_doc ?? "";
                $BblClientes->telefono=$decoded->data->telefono;
                $BblClientes->contrasena=$decoded->data->contrasena;
                $BblClientes->razon_social=$decoded->data->razon_social ?? "";
                $BblClientes->save();
                $BblClientesPasarela = new BblClientesPasarelas();
                if (isset($decoded->data->origin) && $decoded->data->origin === 'epayco') {
                    $BblClientesPasarela->cliente_id = $decoded->data->cliente_id ?? "";
                    $BblClientesPasarela->pasarela_id = 1;
                    $BblClientesPasarela->public_key = $decoded->data->public_key ?? "";
                    $BblClientesPasarela->private_key = $decoded->data->private_key ?? "";
                    $BblClientesPasarela->save();
                }

            } else {
                $BblClientesPasarela = BblClientesPasarelas::where('cliente_id', $bblClientes->id)->where('estado', true)->first();
            }
            $response = array(
                'success' => true,
                'titleResponse' => "authorized",
                'textResponse' => "authorized",
                'lastAction' => "",
                'data' => [
                    'token' => $this->jwt($BblClientesPasarela, null)
                ]
            );
            return $this->crearRespuesta($response);
        } catch (\Exception $exception) {
            $response = array(
                'success' => false,
                'titleResponse' => "Unauthorized",
                'textResponse' => "Unauthorized",
                'lastAction' => "",
                'data' => []
            );
            return $this->crearRespuesta($response);
        }

    }
    /**
     * Busca en los headers la cabecera con el id del cliente hijo de la entidad
     *
     * @return string|null
     */
    public function getHeaderEntityClienteId()
    {
        $header = $this->request->header('EntityClientId');

        if (!empty($header)) {
            $header = base64_decode($header);
        }

        return $this->escape($header);
    }

    /**
     * Consulta si un client_id pertenece a un client_id de la entidad aliada padre
     *
     * @param mixed $parent_id
     * @param mixed $child_id
     * @return bool
     */
    public function validateEntityClienteId($parent_id, $child_id)
    {
        return DB::table('apify_clientes')
            ->join('clientes', 'apify_clientes.cliente_id', '=', 'clientes.id')
            ->where('apify_clientes.apify_cliente_id', '=', $parent_id)
            ->where('cliente_id', $child_id)
            ->count() > 0;
    }

    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     *
     * @return mixed
     */
    public function authenticate()
    {

        $header = $this->request->header('Authorization', '');
        $public_key = "";
        $private_key = "";

        if (Str::startsWith($header, 'Basic ')) {
            $login = explode(":", base64_decode(Str::substr($header, 6)));
            $public_key = $login[0];
            $private_key = $login[1];
            $this->checkNewUser($public_key,$private_key);
        }

        if ($public_key == "") {
            return response()->json([
                'error' => __("error.field required", ['field' => 'public_key'])
            ], 400);
        }
        if ($private_key == "") {
            return response()->json([
                'error' => __("error.field required", ['field' => 'private_key'])
            ], 400);
        }

        $public_key = $this->escape($public_key);
        $private_key = $this->escape($private_key);

        $user = BblClientesPasarelas::where('public_key', $public_key)->where('estado', true)->first();
        if (!$user) {
            return response()->json([
                'error' => __('Client does not exist, Or keys not registered'),
                'register'=>'true'//notificar que debe registrar un nuevo cliente
            ], 400);
            
        }
        if ($private_key == $user->private_key) {
            return response()->json([
                'token' => $this->jwt($user, $user->BblPasarela->nombre)
            ], 200);
        }
        // Bad Request response
        return response()->json([
            'error' => __('Invalid keys.')
        ], 400);
    }

    private function checkNewUser($public_key,$private_key){
        $header = $this->request->header('jwtUserData', '');
        if($header=='') return;
        $codeJwt=Config::get('app.jwt_secret');
        $decoded = JWT::decode($header, $codeJwt, array('HS256'));
        $dataUser=json_decode($this->decryptData($decoded->data));
        $bblClientes= BblClientes::find($dataUser->id);
        if(!$bblClientes){
            $BblClientes                =new BblClientes();
            $BblClientes->id            =$dataUser->id;
            $BblClientes->email         =$dataUser->email;
            $BblClientes->nombre        =$dataUser->nombre ?? "";
            $BblClientes->apellido      =$dataUser->apellido ?? "";
            $BblClientes->documento     =$dataUser->documento;
            $BblClientes->tipo_doc      =$dataUser->tipoDoc ?? "";
            $BblClientes->telefono      =$dataUser->telefono;
            $BblClientes->contrasena    =$dataUser->contrasena ?? "";
            $BblClientes->password      =$dataUser->password;
            $BblClientes->razon_social  =$dataUser->razonSocial ?? "";
            $BblClientes->url           =$this->convertUrl($dataUser->url);
            $BblClientes->save();
            $BblClientesPasarela = new BblClientesPasarelas();
            if (isset($dataUser->origin) && $dataUser->origin === 'epayco') {
                $BblClientesPasarela->cliente_id    = $dataUser->id ?? "";
                $BblClientesPasarela->pasarela_id   = 1;
                $BblClientesPasarela->public_key    = $public_key ?? "";
                $BblClientesPasarela->private_key   = $private_key ?? "";
                $BblClientesPasarela->key_cli       = $dataUser->keyCli ?? "";
                $BblClientesPasarela->save();
            }

        }
    }

    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     *
     * @return mixed
     */
    public function authenticate_email()
    {
        $header = $this->request->header('Authorization', '');
        $login = "";
        $public_key = $this->request->header('public-key', '');
        if (Str::startsWith($header, 'Basic ')) {
             $login = explode(":", base64_decode(Str::substr($header, 6)));
             $email = $login[0];
             array_shift($login);
             $password = implode(":",$login);
        }
        
        if ($email == "") {
            return response()->json([
                'error' => __("error.field required", ['field' => 'email'])
            ], 400);
        }
        if ($password == "") {
            return response()->json([
                'error' => __("error.field required", ['field' => 'password'])
            ], 400);
        }
        
        $email = $this->escape($email);
        $password = $this->escape($password);

        if ($public_key !== '') {
            // si llega public key se valida exactamente cual es el cliente
            $public_key = $this->escape($public_key);
            $userKeys = BblClientesPasarelas::where('public_key', $public_key)->where('estado', true)->first();
            if (!($userKeys && is_object($userKeys))) {
                return response()->json([
                    'error' => 'The public key was not found'
                ], 400);
            }
        } else {
            $userKeys = BblClientesPasarelas::select('bbl_clientes_pasarelas.*')->join('bbl_clientes', 'bbl_clientes.id', 'bbl_clientes_pasarelas.cliente_id')
                ->where('bbl_clientes.email', $email)->where('bbl_clientes_pasarelas.estado', true)->first();
            if (!$userKeys) {
                return response()->json([
                    'error' => __('Client does not exist.')
                ], 400);
            }

            if (password_verify($password, $userKeys->BblCliente->password)) {
                return response()->json([
                    'token' => $this->jwt($userKeys, $userKeys->BblPasarela->nombre)
                ], 200);
            }
    
        }
        // Bad Request response
        return response()->json([
            'error' => __('Invalid Username or Password.')
        ], 400);
    }

    public function autenticacion_epayco()
    {
        $header = $this->request->header('Authorization', '');
        $login = "";
        $public_key = "";
        $private_key = "";

        if (Str::startsWith($header, 'Basic ')) {
            $login = explode(":", base64_decode(Str::substr($header, 6)));
            $public_key = $login[0];
            $private_key = $login[1];
        }

        if ($public_key == "") {
            return response()->json([
                'error' => __("error.field required", ['field' => 'public_key'])
            ], 400);
        }

        if ($private_key == "") {
            return response()->json([
                'error' => __("error.field required", ['field' => 'private_key'])
            ], 400);
        }

        $public_key = $this->escape($public_key);
        $private_key = $this->escape($private_key);
        // Find the user by email
        $user = BblClientesPasarelas::where('public_key', $public_key)->where('estado', true)->first();

        if (!$user) {
            return response()->json([
                'error' => __("Client does not exist.")
            ], 400);
        }

        // Verify the password and generate the token
        if ($user->cliente_id == env("CLIENT_ID_BABILONIA")) {
            // Verificar si se envio un usuario hijo de una entidad aliada

            if ($private_key == $user->private_key) {
                return response()->json([
                    'token' => $this->jwt($user)
                ], 200);
            }
        } else {
            return response()->json([
                'error' => __('permission denied.')
            ], 400);
        }

        // Bad Request response
        return response()->json([
            'error' => __('Invalid keys.')
        ], 400);
    }
    private function decryptData($encodedData) {
        // Decodificar en base64
        $combinedData = base64_decode($encodedData);
    
        // Extraer el IV y los datos encriptados
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($combinedData, 0, $ivLength);
        $encryptedData = substr($combinedData, $ivLength);
    
        // Desencriptar los datos usando AES-256-CBC
        $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', Config::get('app.jwt_secret'), 0, $iv);
    
        return $decryptedData;
    }
    private function convertUrl($urlVieja)
    {
        if ($urlVieja === null) {
            return null;
        }
        $dominioEsperado = config('app.BASE_URL_BBL');
        $dominioEsperado = parse_url($dominioEsperado)['host'];
        $partes = explode('.', $dominioEsperado);
        $dominioPrincipalEsperado = 'shops.'.$partes[count($partes) - 2] . '.' . $partes[count($partes) - 1];

        $dominioViejo = $urlVieja;
        $dominioViejo = parse_url($dominioViejo)['host'];
        $partesDominioViejo = explode('.', $dominioViejo);
        $dominioViejoPrincipal = $partesDominioViejo[count($partesDominioViejo) - 2] . '.' . $partesDominioViejo[count($partesDominioViejo) - 1];

        $urlNueva = str_replace($dominioViejoPrincipal, $dominioPrincipalEsperado, $urlVieja);

        return $urlNueva;
    }
    
}
