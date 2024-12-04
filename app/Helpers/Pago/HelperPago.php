<?php

namespace App\Helpers\Pago;

use App\Exceptions\GeneralException;
use App\Helpers\Messages\CommonText;
use App\Http\Controllers\Controller as Controller;
use App\Http\Lib\Utils;
use App\Http\Validation\Validate;
use App\Models\ApiIpList;
use App\Models\BblClientes;
use App\Models\BblClientesPasarelas;
use App\Models\CatalogoProductos;
use App\Models\Clientes;
use App\Models\DaviplataConfig;
use App\Models\DetalleConfClientes;
use App\Models\LlavesClientes;
use App\Models\LogElasticRecaudoFacturas;
use App\Models\LogRest;
use App\Models\MediosPago;
use App\Models\PaypalClientes;
use Aws\S3\S3Client;
use Epayco\Epayco as SdkePayco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Search;
use OpenCloud\Rackspace;
use WpOrg\Requests\Requests;

class HelperPago extends Controller
{

    public $LLavesclientes;
    public $input_key;
    public $private_key;
    public $request;
    public $enpruebas;
    public $p_key;
    public $fixed_hash_payvalida;
    public $url_media = 'https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com';
    public $p_idcustomer;
    public $appId;
    public $merchantId;
    public $receivers;
    public $split_type;
    public $primary_receiver;
    public $primary_receiver_fee;
    public $splitpayment;
    public $arr_receivers;

    public $public_key;
    private $epayco;

    /** @var string $tokenApifyPrivate */
    public $tokenApifyPrivate;
    public $tokenApify;
    public $tokenApifyPrivateElogisita;
    public $tokenApiSuscripciones;

    public $countLogin = 0;

    private $cacheCabecera = "cache-control: no-cache";
    private $acceptCabecera = "accept: application/json";
    private $contentTypeCabecera = "content-type: application/json";

    public function __construct(Request $request)
    {
        //parent::__construct($request);
        $this->request = $request;
        $this->p_key = "0f76fdd58e1a94531c226a30e6a81197282897b3";
        $this->input_key = 'hE+;=K#oOHfgHK!A!g?QS5e5';
        $this->p_idcustomer = env("CLIENT_ID_APIFY_PRIVATE");
        $this->rest = "https://secure.payco.co/apprest/";
        $this->fixed_hash_payvalida =
            'bf458a9216fc01056ffd8949a731cc29bca33f3f784b79642d61edc157a9a864a7bcb6e3dca852b8bce5283a93b2a07e62224b9bbb61b6259878898ba7add5af';
        $this->url_media =
            'https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com';

        //dd($this->private_key);

        $this->appId = '';
        $this->merchantId = '';
        $this->receivers = '';
        $this->split_type = '';
        $this->primary_receiver = '';
        $this->primary_receiver_fee = '';
        $this->splitpayment = false;
        $this->arr_receivers = array();

    }

    public function getErrorCheckout($codigo)
    {
        return (object) [
            "error_code" => $codigo,
            "error_message" => "Error interno en el servidor, no se logró realizar la acción",
            "error_description" => "Error interno en el servidor, no se logró realizar la acción",
        ];
    }

    public function saveLog($tipo, $clienteId, $request = "", $response = "", $accion = "")
    {

        $id = uniqid('', true);
        $util = new Utils();

        //        if ($tipo == 1) {
        //            $_SESSION["logid"] = $id;

        $log = new LogRest();
        $log->session_id = $id;
        $log->cliente_id = $clienteId;
        $log->fechainicio = new \DateTime('now');
        //Pregunta: ¿setear fechafin tambien?
        //$log->fechafin = (new \DateTime())->format('Y-m-d H:i:s');
        $log->request = json_encode($request);
        $log->microtime = $util->microtime_float();
        $log->ip = $util->getRealIP();

        if ($accion != "") {
            $log->accion = $accion;
        }
        $log->save();

    }

    public function optionalSaveLog($type, $data, $clientId = null, $action = "default", $session = null)
    {
        $util = new Utils();
        if ($type == "request") {
            $log = new LogRest();
            $log->session_id = uniqid('', true);
            $log->cliente_id = $clientId;
            $log->fechainicio = new \DateTime('now');
            $log->request = json_encode($data);
            $log->microtime = $util->microtime_float();
            $log->ip = $util->getRealIP();
            $log->accion = $action;
            $log->save();
        } else {
            $log = LogRest::where('session_id', $session)->first();
            if ($log) {
                $log->fechafin = new \DateTime('now');
                $microfin = $util->microtime_float();
                $totalmicro = $microfin - $log->microtime;
                $log->microtime = $totalmicro;
                $log->response = json_encode($data);
                $log->save();
            }
        }

        return $log->session_id;
    }

    public function validarIp($ip)
    {
        $ipAutorizada = ApiIpList::where('ip', $ip)->first();
        return ($ipAutorizada) ? true : false;
    }

    public function setClientDetail($value, $clientId, $confId)
    {
        $arClienteDetalle = DetalleConfClientes::where("cliente_id", $clientId)->where('config_id', $confId)->first();
        $save = true;
        if ($confId == 6 || $confId == 5 || $confId == 24) {
            $save = $value == "" ? false : true;
            if ($confId == 24) {
                $save = $value;
            }
        }
        if ($save) {
            if (!$arClienteDetalle) {
                $arClienteDetalle = new DetalleConfClientes();
                $arClienteDetalle->config_id = $confId;
                $arClienteDetalle->cliente_id = $clientId;
            }
            $arClienteDetalle->valor = $value;
            $arClienteDetalle->save();
        } else {
            if ($arClienteDetalle) {
                $arClienteDetalle->delete();
                $delete = true;
            }
        }
        if (isset($delete)) {
            return true;
        }
        return $arClienteDetalle;
    }

    public function getClientInfoBasic($client, $client_detail, $more_info = false)
    {

        $balance = array();

        if ($client_detail) {

            $saldo_disponible = $client_detail->saldo_disponible;
            $saldo_congelado = $client_detail->saldo_retenido;
            $saldo_reservado = $client_detail->saldo_reserva;
            $saldo_total = ($saldo_disponible + $saldo_congelado + $saldo_reservado);

            //Se quema por ahora cop porque no se tiene como tal una tabla donde tengamos este valor por id_cliente o de forma general
            $currency_epayco = 'COP';

            $balance['epayco'] = array('available' => $saldo_disponible, 'frozen' => $saldo_congelado, 'reserved' => $saldo_reservado, 'total' => $saldo_total, 'currency' => $currency_epayco);

            //Consultamos si existe en la tabla de paypal traemos el saldo paypal
            $existeSaldoPaypal = PaypalClientes::where('cliente_id', $client->Id)->get()->first();

            if ($existeSaldoPaypal) {

                $balance['paypal'] = array('available' => $existeSaldoPaypal->user_balance, 'total' => $existeSaldoPaypal->user_balance, 'currency' => $existeSaldoPaypal->moneda_balance);
            }
        }

        $client_data = array('id_client' => $client->Id, 'firstNames' => $client->nombre, 'lastNames' => $client->apellido, 'socialName' => $client->razon_social, 'companyName' => $client->nombre_empresa, 'mail' => $client->email, 'mobilePhone' => $client->celular);

        $data = ['client' => $client_data, 'balance' => $balance];
        if (is_array($more_info)) {
            $mas_data = array_merge($data, $more_info);
        } else {
            $mas_data = $data;
        }

        return $mas_data;
    }

    public function getAccountNumber($number, $type, $document, $typeDocument, $numberComplete)
    {
        return $this->encryptedAccount($numberComplete);
    }

    public function encryptedAccount(&$number)
    {
        return file_get_contents("https://soap.epayco.co/soapclientes/encryptar?encriptar=$number");
    }

    public function escape($value)
    {
        $return = '';
        for ($i = 0; $i < strlen($value); ++$i) {
            $char = $value[$i];
            $ord = ord($char);
            if ($char !== "'" && $char !== "\"" && $char !== '\\' && $ord >= 32 && $ord <= 126) {
                $return .= $char;
            } else {
                $return .= '\\x' . dechex($ord);
            }

        }
        return $return;
    }

    public function decryptProductNumber($productNumber)
    {

        $arDaviPlataConfig = DaviPlataConfig::where('id', '=', 1)->get()->first();

        $clientIdDaviviendaCatalogoProd = str_replace("-", '', $arDaviPlataConfig->client_id_davivienda_catalogo_prod);

        //NOTA: este clientId es el de pruebas, al momenot de subir a produccion cambiarlo.
        $key = hex2bin($clientIdDaviviendaCatalogoProd . "" . $clientIdDaviviendaCatalogoProd);
        $iv = hex2bin("00000000000000000000000000000000");

        $result = openssl_decrypt(hex2bin($productNumber), 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        return str_replace(" ", "", $result);

    }

    public function uploadDocumentosLegales($nameFile, $tmp_name)
    {
        return $this->uploadFile('media', $tmp_name, $nameFile, 'cobros/files/');
    }

    public function uploadDocumentosTicket($nameFile, $tmp_name)
    {
        return $this->uploadFile('media', $tmp_name, $nameFile, 'tickets/');
    }

    public function getPaises()
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                //CURLOPT_PORT => "1443",
                //CURLOPT_URL => "https://apilab.davivienda.com:1443/davivienda/publico/daviplata/v1/compra",
                CURLOPT_URL => "https://secure.payco.co/apprest/paises.json",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "accept: application/json",
                    "content-type: application/json",

                ),
            ));
            $resp = curl_exec($curl);
            curl_close($curl);
            return json_decode($resp);

        } catch (\Exception $exception) {
            return $exception;

        }
    }

    public function uploadFile($container, $localfile, $filename, $path)
    {
        $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
            'username' => getenv("RACKSPACE_CLIENT_USERNAME"),
            'apiKey' => getenv("RACKSPACE_CLIENT_API_KEY"),
        ));

        // Obtain an Object Store service object from the client.
        $objectStoreService = $client->objectStoreService(null, getenv('RACKSPACE_SERVICE_REGION'));

        $container = $objectStoreService->getContainer($container);

        $handle = fopen($localfile, 'r');
        return $container->uploadObject("{$path}/{$filename}", $handle);

    }

    public function uploadFileAws($bucketName, $localfile, $path, $getResponse = false)
    {
        $credentials = [
            'version' => getenv("AWS_VERSION"),
            'region' => getenv("AWS_REGION"),
        ];

        if (getenv("AWS_KEY")) {
            $credentials["credentials"] = [
                'key' => getenv("AWS_KEY"),
                'secret' => getenv("AWS_SECRET"),
            ];
        }

        $s3 = new S3Client($credentials);

        try {

            $s3->putObject([
                'Bucket' => $bucketName,
                'Key' => $path,
                'Body' => fopen($localfile, 'r'),
                'ACL' => 'public-read',
            ]);

            if ($getResponse) {
                $arr_respuesta[CommonText::TITLE_RESPONSE] = true;
                $arr_respuesta[CommonText::TEXT_RESPONSE] = 'File was saved Successfully';
                $arr_respuesta[CommonText::LAST_ACTION] = 'Save file';
                $arr_respuesta['data'] = $path;

                return $arr_respuesta;
            }

        } catch (\Aws\S3\Exception\S3Exception $e) {

            $arr_respuesta[CommonText::TITLE_RESPONSE] = false;
            $arr_respuesta[CommonText::TEXT_RESPONSE] = 'There was a problem saving';
            $arr_respuesta[CommonText::LAST_ACTION] = 'Save file';
            $arr_respuesta['data'] = [];

            return $arr_respuesta;
        }

    }

    public function ElasticCurl($url, $body, $port = 9200, $type = "POST", $authentication = false)
    {
        $curl = curl_init();
        $authentication = ($authentication === true || $authentication == "true");
        $username = getenv("ELASTIC_USER");
        $password = getenv("ELASTIC_PASS");
        $basic = base64_encode($username . ":" . $password);

        $headers = array(
            "Accept: */*",
            "Accept-Encoding: gzip, deflate",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "Content-Type: application/json",
        );

        if ($authentication) {
            array_push($headers, "Authorization: Basic $basic");
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_PORT => $port,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "", // This ensures cURL decodes gzip content if server sends it
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            return response()->json(['respuesta' => "cURL Error #:" . $err]);
        } else {
            $responseData = json_decode($response);

            if ($responseData) {
                $responseData->status_code = $httpStatusCode;
            }

            return response()->json($responseData);
        }
    }

    public function sendCurlVariables($url, $variables, $tipo_envio, $json = false)
    {

        $postvars = '';
        $par_clientes = array();

        foreach ($variables as $key => $value) {
            $postvars .= $key . "=" . $value . "&";
        }
        if ($tipo_envio == 'GET') {
            $par_clientes = explode('?', $url);
            $arr_vars_cliente = array();
            if (count($par_clientes) >= 2) {
                if (count($par_clientes) > 2) {
                    for ($i = 2; $i < count($par_clientes); $i++) {
                        $par_clientes[1] .= "?" . $par_clientes[$i];
                    }
                }
                $query = explode('&', $par_clientes[1]);

                foreach ($query as $key => $value) {
                    $vars = explode('=', $value);
                    $varkey = $vars[0];
                    $valor = $vars[1];
                    $arr_vars_cliente[$varkey] = $valor;
                }
                $var_adicionales = '?' . http_build_query($arr_vars_cliente) . '&';
            } else {
                $var_adicionales = "?";
                $par_clientes[0] = $url;
            }
            $url = $par_clientes[0] . $var_adicionales . http_build_query($variables);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        if ($tipo_envio == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if (!$json) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
            } //0 for a get request
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10000); //timeout in seconds

        if ($json) {
            $varEncode = json_encode($variables);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $varEncode);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($varEncode),
                )
            );
        }

        $response = curl_exec($ch);

        curl_close($ch);
        $arrRequests = explode("\r\n\r\n", $response);

        $body = end($arrRequests);

        $header_code = '500';
        $header = $this->get_headers_from_curl_response($response);
        if ($header) {
            $code_header = ($header[0]['http_code']);
            $exp_header = explode(" ", $code_header);

            if (is_array($exp_header)) {
                if (isset($exp_header[1])) {
                    $header_code = $exp_header[1];
                }
            }
            return array('header_code' => $header_code, 'body' => $body, 'url' => $url);
        } else {
            return array('header_code' => $header_code, 'body' => $body, 'url' => $url);
        }
    }

    public function sendEmailEdataReglas($url)
    {

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $this->urlMail($url),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_VERBOSE => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CONNECTTIMEOUT => 0,
                CURLOPT_TIMEOUT => 10000,
                CURLOPT_HTTPHEADER => [
                    "cache-control: no-cache",
                    "accept: application/json",
                    "content-type: application/json",
                ],
            ]
        );

        $response = curl_exec($curl);
        curl_close($curl);
        $arrRequests = explode("\r\n\r\n", $response);
        $body = end($arrRequests);
        $header_code = '500';
        $header = $this->get_headers_from_curl_response($response);
        if ($header) {
            $code_header = ($header[0]['http_code']);
            $exp_header = explode(" ", $code_header);

            if (is_array($exp_header)) {
                if (isset($exp_header[1])) {
                    $header_code = $exp_header[1];
                }
            }
            return array('header_code' => $header_code, 'body' => $body, 'url' => $url);
        } else {
            return array('header_code' => $header_code, 'body' => $body, 'url' => $url);
        }
    }

    public function urlMail($url)
    {
        $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        return str_replace($entities, $replacements, urlencode($url));
    }

    public function apiService($url, $data, $type, $cabecera = null)
    {
        try {

            if ($cabecera) {
                $cabecera = array(
                    $this->cacheCabecera,
                    $this->acceptCabecera,
                    $this->contentTypeCabecera,
                    $cabecera,
                );
            } else {
                $cabecera = array(
                    $this->cacheCabecera,
                    $this->acceptCabecera,
                    $this->contentTypeCabecera,
                );
            }

            $jsonData = json_encode($data);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $type,
                CURLOPT_POSTFIELDS => "{$jsonData}",
                CURLOPT_HTTPHEADER => $cabecera,
            ));
            $resp = curl_exec($curl);
            if ($resp === false) {
                return array('curl_error' => curl_error($curl), 'curerrno' => curl_errno($curl));
            }
            curl_close($curl);
            return json_decode($resp);

        } catch (\Exception $exception) {
            return [
                "success" => false,
                "titleResponse" => "error",
                "textResponse" => $exception->getMessage(),
                "data" => [],
            ];
        }
    }

    private function get_headers_from_curl_response($headerContent)
    {

        $headers = array();

        // Split the string on every "double" new line.
        $arrRequests = explode("\r\n\r\n", $headerContent);
        // Loop of response headers. The "count() -1" is to
        //avoid an empty row for the extra line break before the body of the response.
        for ($index = 0; $index < count($arrRequests) - 1; $index++) {

            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line) {
                if ($i === 0) {
                    $headers[$index]['http_code'] = $line;
                } else {
                    list($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }

        return $headers;
    }

    public function enviarsmscobro($cobro, $indicativo, $compartirpor)
    {

        if ($indicativo == "") {
            $indicativo = "57";
        }

        $cliente = Clientes::where("Id", $cobro->cliente_id)->first();
        if ($cliente->tipo_cliente == 'P') {
            $cliente_nombres = $cliente->nombre . ' ' . $cliente->apellido;
        } else {
            $cliente_nombres = $cliente->razon_social;
        }
        if ($cliente->nombre_empresa != "") {
            $cliente_nombres = $cliente->nombre_empresa;
        }

        if (strlen($cliente_nombres) > 30) {
            $cliente_nombres = substr($cliente_nombres, 0, 30) . '...';
        }
        $cliente_nombres = $this->limpiarstr($cliente_nombres);

        $numerocelular = $compartirpor->valor;
        $txtcodigo = $cobro->txtcodigo;
        $valor = number_format($cobro->valor, 2);
        $texto = "$cliente_nombres, te ha enviado un sms de cobro por: {$valor} {$cobro->moneda}. Puedes pagar via web en: https://payco.link/$txtcodigo";

        $addr = $indicativo . "" . $numerocelular;
        $cont = $texto;

        $dataEnvio = [
            "number" => $addr,
            "message" => $cont,
            "type" => "send",
            "origin" => "dashboard-sms-sell",
        ];
        ///Servicio temporal
        return $this->sendCurlVariables("https://secure.epayco.co/send/sms", $dataEnvio, "POST");
    }

    private function limpiarstr($string)
    {
        $string = htmlentities($string);
        return preg_replace('/\&(.)[^;]*;/', '\\1', $string);
    }

    public function enviaremailcobro($cobro, $compartirpor)
    {

        $id_cobro = $cobro->Id;
        $id_compartir = $compartirpor->id;
        $baseUrlRest = getenv("BASE_URL_REST");
        $baseUrlAppRest = getenv("BASE_URL_APP_REST_ENTORNO");

        $url_email = "{$baseUrlRest}/{$baseUrlAppRest}/email/cobro?id_cobro=$id_cobro&id_compartir=" . $id_compartir;

        return $this->sendCurlVariables($url_email, [], "GET", true);

    }

    public function emailPanelRest($subject, $toEmail, $viewName, $viewParameters = [])
    {
        $baseUrlRest = getenv("BASE_URL_REST");
        $pathPanelAppRest = getenv("BASE_URL_APP_REST_ENTORNO");
        $pametersString = "";
        foreach ($viewParameters as $key => $parameter) {
            if (is_array($parameter) || is_object($parameter)) {
                foreach ($parameter as $key2 => $parameter2) {
                    if (is_array($parameter2) || is_object($parameter2)) {
                        foreach ($parameter2 as $key3 => $parameter3) {
                            $pametersString .= "&viewParameters[$key][$key2][$key3]=$parameter3";
                        }
                    } else {
                        $pametersString .= "&viewParameters[$key][$key2]=$parameter2";
                    }
                }
            } else {
                $pametersString .= "&viewParameters[$key]=$parameter";
            }
        }
        $url_email = "{$baseUrlRest}/{$pathPanelAppRest}/email/send?subject=$subject&toEmail=$toEmail&viewName=$viewName" . $pametersString;

       // return $this->sendCurlVariables($url_email, [], "GET", true);

    }

    public function EmailNotificationsBBL($toEmail, $url, $params = [])
    {

        try {

            $notificationsUrl = config("app.MS_NOTIFICATIONS_BBL_URL");
            $url = "{$notificationsUrl}/{$url}";

            $data = array_merge([
                "recipient" => $toEmail,
            ], $params);

            $res = $this->sendCurlVariables($url, $data, "post", true);

            return $res;
        } catch (\Exception $error) {
            Log::info($error);
        }

    }

    public function buscarPorNombre($data)
    {
        try {
            $zona = getenv('CLOUDFLARE_ZONE');
            $email = getenv('CLOUDFLARE_EMAIL');
            $apikey = getenv('CLOUDFLARE_APIKEY');
            $dominio = getenv('CLOUDFLARE_DOMAIN');

            $ch = curl_init("https://api.cloudflare.com/client/v4/zones/$zona/dns_records?name={$data["subdomain"]}.${dominio}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "X-Auth-Email: $email",
                "X-Auth-Key: $apikey",
                'Content-Type:application/json',
            ));

            $datos = curl_exec($ch);

            curl_close($ch);
            return $datos;
        } catch (\Exception $exception) {
            return $datos;
        }
    }

    public function buscarGeneral($data, $interno = false)
    {
        try {
            $zona = getenv('CLOUDFLARE_ZONE');
            $email = getenv('CLOUDFLARE_EMAIL');
            $apikey = getenv('CLOUDFLARE_APIKEY');
            $dominio = getenv('CLOUDFLARE_DOMAIN');
            $ipServer = getenv("CLOUDFLARE_IP_SERVER");
            $type = getenv("CLOUDFLARE_DOMAIN_TYPE");
            $target = getenv("CLOUDFLARE_TARGET");

            $content = $type === "A" ? $ipServer : $target;
            $ch = curl_init("https://api.cloudflare.com/client/v4/zones/$zona/dns_records?type={$type}&name={$data["subdomain"]}.${dominio}&content={$content}&page=1&per_page=20&order=type&direction=desc&match=all");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "X-Auth-Email: $email",
                "X-Auth-Key: $apikey",
                'Content-Type:application/json',

            ));

            $datos = curl_exec($ch);

            curl_close($ch);
            return $datos;
        } catch (\Exception $exception) {
            return $datos;
        }
    }

    public function crearGeneral($data, $interno = false)
    {
        try {

            $zona = getenv('CLOUDFLARE_ZONE');
            $email = getenv('CLOUDFLARE_EMAIL');
            $apikey = getenv('CLOUDFLARE_APIKEY');
            $target = getenv('CLOUDFLARE_TARGET');
            $type = getenv('CLOUDFLARE_DOMAIN_TYPE');
            $ipServer = getenv('CLOUDFLARE_IP_SERVER');

            $data = array(

                'type' => $type,
                'name' => "{$data["name"]}",
                'ttl' => 1,
                'priority' => 0,
                'proxied' => false,
            );

            if ($type === "A") {
                $data['content'] = $ipServer;
            } else if ($type === "CNAME") {
                $data['content'] = $target;
            }

            $data_string = json_encode($data);

            $ch = curl_init("https://api.cloudflare.com/client/v4/zones/$zona/dns_records");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "X-Auth-Email: $email",
                "X-Auth-Key: $apikey",
                'Content-Type:application/json',

            ));

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

            $datos = curl_exec($ch);

            curl_close($ch);

            return $datos;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function eliminarGeneral($data, $interno = false)
    {
        $datos = json_decode($this->buscarGeneral($data, true), true);
        if ($datos && isset($datos["result"]) && count($datos["result"]) > 0) {
            $zona = getenv('CLOUDFLARE_ZONE');
            $email = getenv('CLOUDFLARE_EMAIL');
            $apikey = getenv('CLOUDFLARE_APIKEY');

            $ch = curl_init("https://api.cloudflare.com/client/v4/zones/$zona/dns_records/{$datos["result"][0]["id"]}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "X-Auth-Email: $email",
                "X-Auth-Key: $apikey",
                'Content-Type:application/json',

            ));

            $response = curl_exec($ch);

            curl_close($ch);
            return $response;
        } else {
            return false;
        }
    }

    public function indicativosPaises()
    {
        return [
            [
                "value" => 355,
                "name" => "AL",

            ],
            [
                "value" => 49,
                "name" => "DE",

            ],
            [
                "value" => 376,
                "name" => "AD",

            ],
            [
                "value" => 244,
                "name" => "AO",

            ],
            [
                "value" => 966,
                "name" => "SA",

            ],
            [
                "value" => 54,
                "name" => "AR",

            ],
            [
                "value" => 374,
                "name" => "AM",

            ],
            [
                "value" => 97,
                "name" => "AW",

            ],
            [
                "value" => 61,
                "name" => "AU",

            ],
            [
                "value" => 43,
                "name" => "AT",

            ],
            [
                "value" => 1002,
                "name" => "BS",

            ],
            [
                "value" => 880,
                "name" => "BD",

            ],
            [
                "value" => 1003,
                "name" => "BB",

            ],
            [
                "value" => 501,
                "name" => "BZ",

            ],
            [
                "value" => 229,
                "name" => "BJ",

            ],
            [
                "value" => 591,
                "name" => "BO",

            ],
            [
                "value" => 387,
                "name" => "BA",

            ],
            [
                "value" => 267,
                "name" => "BW",

            ],
            [
                "value" => 55,
                "name" => "BR",

            ],
            [
                "value" => 673,
                "name" => "BN",

            ],
            [
                "value" => 359,
                "name" => "BG",

            ],
            [
                "value" => 226,
                "name" => "BF",

            ],
            [
                "value" => 257,
                "name" => "BI",

            ],
            [
                "value" => 32,
                "name" => "BE",

            ],
            [
                "value" => 238,
                "name" => "CV",

            ],
            [
                "value" => 237,
                "name" => "CM",

            ],
            [
                "value" => 1,
                "name" => "CA",

            ],
            [
                "value" => 235,
                "name" => "TD",

            ],
            [
                "value" => 56,
                "name" => "CL",

            ],
            [
                "value" => 86,
                "name" => "CN",

            ],
            [
                "value" => 357,
                "name" => "CY",

            ],
            [
                "value" => 57,
                "name" => "CO",

            ],
            [
                "value" => 850,
                "name" => "KP",

            ],
            [
                "value" => 506,
                "name" => "CR",

            ],
            [
                "value" => 225,
                "name" => "CI",

            ],
            [
                "value" => 385,
                "name" => "HR",

            ],
            [
                "value" => 53,
                "name" => "CU",

            ],
            [
                "value" => 45,
                "name" => "DK",

            ],
            [
                "value" => 1007,
                "name" => "DM",

            ],
            [
                "value" => 593,
                "name" => "EC",

            ],
            [
                "value" => 20,
                "name" => "EG",

            ],
            [
                "value" => 503,
                "name" => "SV",

            ],
            [
                "value" => 421,
                "name" => "SK",

            ],
            [
                "value" => 34,
                "name" => "ES",

            ],
            [
                "value" => 1,
                "name" => "US",

            ],
            [
                "value" => 372,
                "name" => "EE",

            ],
            [
                "value" => 63,
                "name" => "PH",

            ],
            [
                "value" => 358,
                "name" => "FI",

            ],
            [
                "value" => 33,
                "name" => "FR",

            ],
            [
                "value" => 220,
                "name" => "GM",

            ],
            [
                "value" => 995,
                "name" => "GE",

            ],
            [
                "value" => 233,
                "name" => "GH",

            ],
            [
                "value" => 350,
                "name" => "GI",

            ],
            [
                "value" => 1009,
                "name" => "GD",

            ],
            [
                "value" => 30,
                "name" => "GR",

            ],
            [
                "value" => 590,
                "name" => "GP",

            ],
            [
                "value" => 1671,
                "name" => "GU",

            ],
            [
                "value" => 502,
                "name" => "GT",

            ],
            [
                "value" => 224,
                "name" => "GN",

            ],
            [
                "value" => 240,
                "name" => "GQ",

            ],
            [
                "value" => 245,
                "name" => "GW",

            ],
            [
                "value" => 592,
                "name" => "GY",

            ],
            [
                "value" => 504,
                "name" => "HN",

            ],
            [
                "value" => 852,
                "name" => "HK",

            ],
            [
                "value" => 91,
                "name" => "IN",

            ],
            [
                "value" => 62,
                "name" => "ID",

            ],
            [
                "value" => 964,
                "name" => "IQ",

            ],
            [
                "value" => 353,
                "name" => "IE",

            ],
            [
                "value" => 98,
                "name" => "IR",

            ],
            [
                "value" => 354,
                "name" => "IS",

            ],
            [
                "value" => 1006,
                "name" => "KY",

            ],
            [
                "value" => 682,
                "name" => "CK",

            ],
            [
                "value" => 692,
                "name" => "MH",

            ],
            [
                "value" => 677,
                "name" => "SB",

            ],
            [
                "value" => 284,
                "name" => "VG",

            ],
            [
                "value" => 972,
                "name" => "IL",

            ],
            [
                "value" => 39,
                "name" => "IT",

            ],
            [
                "value" => 1010,
                "name" => "JM",

            ],
            [
                "value" => 81,
                "name" => "JP",

            ],
            [
                "value" => 962,
                "name" => "JO",

            ],
            [
                "value" => 254,
                "name" => "KE",

            ],
            [
                "value" => 686,
                "name" => "KI",

            ],
            [
                "value" => 965,
                "name" => "KW",

            ],
            [
                "value" => 856,
                "name" => "LA",

            ],
            [
                "value" => 266,
                "name" => "LS",

            ],
            [
                "value" => 231,
                "name" => "LR",

            ],
            [
                "value" => 218,
                "name" => "LY",

            ],
            [
                "value" => 417,
                "name" => "LI",

            ],
            [
                "value" => 370,
                "name" => "LT",

            ],
            [
                "value" => 352,
                "name" => "LU",

            ],
            [
                "value" => 853,
                "name" => "MO",

            ],
            [
                "value" => 261,
                "name" => "MG",

            ],
            [
                "value" => 60,
                "name" => "MY",

            ],
            [
                "value" => 265,
                "name" => "MW",

            ],
            [
                "value" => 960,
                "name" => "MV",

            ],
            [
                "value" => 356,
                "name" => "MT",

            ],
            [
                "value" => 223,
                "name" => "ML",

            ],
            [
                "value" => 212,
                "name" => "MA",

            ],
            [
                "value" => 230,
                "name" => "MU",

            ],
            [
                "value" => 222,
                "name" => "MR",

            ],
            [
                "value" => 2696,
                "name" => "YT",

            ],
            [
                "value" => 691,
                "name" => "FM",

            ],
            [
                "value" => 976,
                "name" => "MN",

            ],
            [
                "value" => 1011,
                "name" => "MS",

            ],
            [
                "value" => 258,
                "name" => "MZ",

            ],
            [
                "value" => 95,
                "name" => "MM",

            ],
            [
                "value" => 52,
                "name" => "MX",

            ],
            [
                "value" => 377,
                "name" => "MC",

            ],
            [
                "value" => 264,
                "name" => "NA",

            ],
            [
                "value" => 674,
                "name" => "NR",

            ],
            [
                "value" => 977,
                "name" => "NP",

            ],
            [
                "value" => 505,
                "name" => "NI",

            ],
            [
                "value" => 227,
                "name" => "NE",

            ],
            [
                "value" => 234,
                "name" => "NG",

            ],
            [
                "value" => 683,
                "name" => "NU",

            ],
            [
                "value" => 47,
                "name" => "NO",

            ],
            [
                "value" => 687,
                "name" => "NC",

            ],
            [
                "value" => 64,
                "name" => "NZ",

            ],
            [
                "value" => 968,
                "name" => "OM",

            ],
            [
                "value" => 92,
                "name" => "PK",

            ],
            [
                "value" => 507,
                "name" => "PA",

            ],
            [
                "value" => 595,
                "name" => "PY",

            ],
            [
                "value" => 51,
                "name" => "PE",

            ],
            [
                "value" => 689,
                "name" => "PF",

            ],
            [
                "value" => 48,
                "name" => "PL",

            ],
            [
                "value" => 351,
                "name" => "PT",

            ],
            [
                "value" => 1787,
                "name" => "PR",

            ],
            [
                "value" => 974,
                "name" => "QA",

            ],
            [
                "value" => 44,
                "name" => "GB",

            ],
            [
                "value" => 42,
                "name" => "CZ",

            ],
            [
                "value" => 1008,
                "name" => "DO",

            ],
            [
                "value" => 262,
                "name" => "RE",

            ],
            [
                "value" => 40,
                "name" => "RO",

            ],
            [
                "value" => 684,
                "name" => "WS",

            ],
            [
                "value" => 684,
                "name" => "AS",

            ],
            [
                "value" => 378,
                "name" => "SM",

            ],
            [
                "value" => 221,
                "name" => "SN",

            ],
            [
                "value" => 248,
                "name" => "SC",

            ],
            [
                "value" => 232,
                "name" => "SL",

            ],
            [
                "value" => 65,
                "name" => "SG",

            ],
            [
                "value" => 963,
                "name" => "SY",

            ],
            [
                "value" => 252,
                "name" => "SO",

            ],
            [
                "value" => 94,
                "name" => "LK",

            ],
            [
                "value" => 27,
                "name" => "ZA",

            ],
            [
                "value" => 249,
                "name" => "SD",

            ],
            [
                "value" => 46,
                "name" => "SE",

            ],
            [
                "value" => 41,
                "name" => "CH",

            ],
            [
                "value" => 597,
                "name" => "SR",

            ],
            [
                "value" => 66,
                "name" => "TH",

            ],
            [
                "value" => 886,
                "name" => "TW",

            ],
            [
                "value" => 255,
                "name" => "TZ",

            ],
            [
                "value" => 228,
                "name" => "TG",

            ],
            [
                "value" => 676,
                "name" => "TO",

            ],
            [
                "value" => 1016,
                "name" => "TT",

            ],
            [
                "value" => 993,
                "name" => "TM",

            ],
            [
                "value" => 688,
                "name" => "TV",

            ],
            [
                "value" => 256,
                "name" => "UG",

            ],
            [
                "value" => 598,
                "name" => "UY",

            ],
            [
                "value" => 737,
                "name" => "UZ",

            ],
            [
                "value" => 7377,
                "name" => "VU",

            ],
            [
                "value" => 58,
                "name" => "VE",

            ],
            [
                "value" => 84,
                "name" => "VN",

            ],
            [
                "value" => 681,
                "name" => "WF",

            ],
            [
                "value" => 967,
                "name" => "YE",

            ],
            [
                "value" => 260,
                "name" => "ZM",

            ],
        ];
    }

    public function productValitation($tipoIdentificacion, $numeroIdentificacion, $reference, $accountType, $baseUrlRest)
    {
        //{"tipoIdentificacion":"CC","numeroIdentificacion":"1010205354","reference":"8274","accountType":"CA"}
        $arDatos = array(
            'tipoIdentificacion' => $tipoIdentificacion,
            'numeroIdentificacion' => $numeroIdentificacion,
            'reference' => $reference,
            'accountType' => $accountType,
        );
        try {
            $jsonData = json_encode($arDatos);
            //$urlService = "{$baseUrlRest}/restpagos/index.php/api/davivienda/product/validation";
            $urlService = "https://restapi.epayco.co/api/davivienda/product/validation";
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $urlService,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{$jsonData}",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "accept: application/json",
                    "content-type: application/json",

                ),
            ));
            $resp = curl_exec($curl);
            if ($resp === false) {
                return array('curl_error' => curl_error($curl), 'curerrno' => curl_errno($curl));
            }
            curl_close($curl);
            return json_decode($resp);
        } catch (\Exception $exception) {
            return $exception;
        }
    }

    public function getKeyClient($clientId)
    {
        return LlavesClientes::where('cliente_id', $clientId)->first();
    }

    public function getIntegrationPhase($clientId)
    {
        $arCliente = Clientes::where("Id", $clientId)->first();
        $integrationPhase = true;
        if ($arCliente) {
            $integrationPhase = $arCliente->fase_integracion == 2 ? false : true;
        }

        return $integrationPhase;
    }

    //////////////////////////// PROCESAR TRANSACCIONES Y SUSCRIPCIONES ///////////////////////////////////////////////////////////////////////////////

    private function initSdk($clientId, $testMode = null)
    {

        $llaves = $this->getKeyClient($clientId);
        if (gettype($testMode) == 'boolean') {
            $integrationPhase = $testMode;
        } else {
            $integrationPhase = $this->getIntegrationPhase($clientId);
        }

        if ($llaves) {
            $this->private_key = $llaves->private_key_decrypt;
            $this->public_key = $llaves->public_key;
        }
        $this->epayco = false;

        if (is_object($llaves)) {
            $this->epayco = new SdkePayco(array(
                "apiKey" => $this->public_key,
                "privateKey" => $this->private_key,
                "lenguage" => "ES",
                "test" => $integrationPhase,
            ));
        }
    }

    public function initSdkBbl($testMode = false)
    {

        $this->public_key = env('BBL_PUBLIC_KEY');
        $this->private_key = env('BBL_PRIVATE_KEY');

        $this->epayco = false;

        $this->epayco = new SdkePayco(array(
            "apiKey" => $this->public_key,
            "privateKey" => $this->private_key,
            "lenguage" => "ES",
            "test" => $testMode,
        ));
        return $this->epayco;
    }

    public function tokenMongoDb($card, $clientId, $test = null)
    {
        $tokenMongoDb = false;
        if (is_array($card)) {
            $this->initSdk($clientId, $test);
            if (isset($this->epayco) && $this->epayco) {
                $tokenMongoDb = $this->epayco->token->create(array("card[number]" => $card["number"], "card[exp_year]" => $card["expYear"], "card[exp_month]" => $card["expMonth"], "card[cvc]" => $card["cvc"], 'ip' => '127.0.0.1', "v4l1d4t3" => $card["v4l1d4t3"]));
            }
        }

        return $tokenMongoDb;
    }
    public function tokenMongoDbBbl($card, $clientId, $test = null)
    {
        $tokenMongoDb = false;
        if (is_array($card)) {
            $this->initSdkBbl($test);
            if (isset($this->epayco) && $this->epayco) {
                $tokenMongoDb = $this->epayco->token->create(array("card[number]" => $card["number"], "card[exp_year]" => $card["expYear"], "card[exp_month]" => $card["expMonth"], "card[cvc]" => $card["cvc"], 'ip' => '127.0.0.1', "v4l1d4t3" => $card["v4l1d4t3"]));
            }
        }

        return $tokenMongoDb;
    }
    public function chargeBbl($arrData, $clientId, $test = null)
    {
        $tokenMongoDb = false;
        if (is_array($arrData)) {
            $this->initSdkBbl($test);
            if (isset($this->epayco) && $this->epayco) {
                $tokenMongoDb = $this->epayco->subscriptions->charge($arrData);
            }
        }

        return $tokenMongoDb;
    }
    public function getTransactionBbl($idTransaction)
    {
        $this->initSdkBbl();
        return $this->epayco->charge->transaction($idTransaction);
    }
    public function cancelSubscriptionsBbl($idSubscription, $clientId, $test = null)
    {

        if (isset($idSubscription)) {
            $this->initSdkBbl($test);
            if (isset($this->epayco) && $this->epayco) {
                $res = $this->epayco->subscriptions->cancel($idSubscription);
            }
        }

        return $res;
    }

    public function deleteTokenMongoDb($card, $clientId)
    {

        $deleteTokenMongoDb = false;
        if (is_array($card)) {
            $this->initSdkBbl();
            if (isset($this->epayco) && $this->epayco) {
                $deleteTokenMongoDb = $this->epayco->customer->delete($card);
            }

        }

        return $deleteTokenMongoDb;
    }

    public function customerMongoDb($clientId, $data)
    {
        $customer = false;
        if (is_array($data)) {
            $this->initSdk($clientId);
            if (isset($this->epayco) && $this->epayco) {
                $customer = $this->epayco->customer->create($data);
            }

        }

        return $customer;
    }

    public function customerUpdateMongoDb($clientId, $customerId, $data)
    {
        $customer = false;
        if (is_array($data)) {
            $this->initSdk($clientId);
            if (isset($this->epayco) && $this->epayco) {
                $customer = $this->epayco->customer->update($customerId, $data);
            }

        }

        return $customer;
    }

    public function addNewToken($clientId, $data)
    {
        $customer = false;
        if (is_array($data)) {
            $this->initSdk($clientId);
            if (isset($this->epayco) && $this->epayco) {
                $customer = $this->epayco->customer->addNewToken($data);
            }

        }

        return $customer;
    }

    public function addNewTokenDefaultCard($clientId, $data)
    {
        $customer = false;
        if (is_array($data)) {
            $this->initSdk($clientId);
            if (isset($this->epayco) && $this->epayco) {
                $customer = $this->epayco->customer->addDefaultCard($data);
            }

        }

        return $customer;
    }
    public function getCustomerBblV2($customerId)
    {
        $customer = false;
        if ($customerId) {
            $this->initSdkBbl();
            if (isset($this->epayco) && $this->epayco) {
                $customer = $this->epayco->customer->get($customerId);
            }

        }

        return $customer;
    }
    public function getCustomerBbl($clientId, $customerId)
    {
        $customer = false;
        if ($customerId) {
            $this->initSdkBbl();
            if (isset($this->epayco) && $this->epayco) {
                $customer = $this->epayco->customer->get($customerId);
            }

        }

        return $customer;
    }

    public function getCustomerMongoDb($clientId, $data)
    {
        $customer = false;
        if ($data) {
            $this->initSdk($clientId);
            if (isset($this->epayco) && $this->epayco) {
                $customer = $this->epayco->customer->get($data);
            }

        }

        return $customer;
    }

    public function getCustomersMongoDb($clientId)
    {
        $customer = false;
        $this->initSdk($clientId);
        if (isset($this->epayco) && $this->epayco) {
            $customer = $this->epayco->customer->getList();
        }

        return $customer;
    }
    public function createSubscriptionsBbl($data)
    {
        $this->initSdkBbl();
        if (isset($this->epayco) && $this->epayco) {
            $subscription = $this->epayco->subscriptions->create($data);
        }

        return $subscription;
    }
    public function addDefaultCardBbl($data)
    {
        $this->initSdkBbl();
        $customer = false;
        if (isset($this->epayco) && $this->epayco) {
            $customer = $this->epayco->customer->addDefaultCard($data);
        }

        return $customer;
    }
    public function getSubscriptionsBbl($idSubscription)
    {
        $this->initSdkBbl();
        $subscription = $this->epayco->subscriptions->get($idSubscription);
        return $subscription;
    }

    public function createTransaction($clientId, $data, $testMode)
    {
        $pay = false;
        if (is_array($data)) {
            $this->initSdk($clientId, $testMode);
            if (isset($this->epayco) && $this->epayco) {
                $pay = $this->epayco->charge->create($data);
            }

        }

        return $pay;
    }

    public function createTransactionPse($clientId, $data, $testMode)
    {
        $pay = false;
        if (is_array($data)) {
            $this->initSdk($clientId, $testMode);
            if (isset($this->epayco) && $this->epayco) {
                $pay = $this->epayco->bank->create($data);
            }

        }

        return $pay;
    }

    public function getPseBanksSdk($clientId, $test)
    {
        $pseBanks = false;
        $this->initSdk($clientId);
        if (isset($this->epayco) && $this->epayco) {
            $pseBanks = $this->epayco->bank->pseBank($test);
        }

        return $pseBanks;
    }

    public function getPseTransaction($clientId, $transactionId)
    {
        $pseTransaction = false;

        $this->initSdk($clientId);
        if (isset($this->epayco) && $this->epayco) {
            $pseTransaction = $this->epayco->bank->get($transactionId);
        }

        return $pseTransaction;
    }

    public function createTransactionCash($clientId, $data, $testMode, $paymentMethod)
    {
        $pay = false;
        if (is_array($data)) {
            $this->initSdk($clientId, $testMode);
            if (isset($this->epayco) && $this->epayco) {
                $pay = $this->epayco->cash->create($paymentMethod, $data);
            }
        }

        return $pay;
    }

    public function getAllTransaction($clientId, $transactionId)
    {
        $trx = false;

        $this->initSdk($clientId);
        if (isset($this->epayco) && $this->epayco) {
            $trx = $this->epayco->cash->transaction($transactionId);
        }
        return $trx;
    }

    public function translateTransaction($data)
    {
        if (!(array) $data) {
            return [];
        }

        if (isset($data->errores)) {
            return $data;
        }

        return [
            "clientId" => $data->x_cust_id_cliente,
            "refPayco" => $data->x_ref_payco,
            "invoice" => $data->x_id_invoice,
            "description" => $data->x_description,
            "amount" => $data->x_amount,
            "amountCountry" => $data->x_amount_country,
            "amountOk" => $data->x_amount_ok,
            "tax" => $data->x_tax,
            "ico" => $data->x_tax_ico ?? 0,
            "baseTax" => $data->x_amount_base,
            "currency" => $data->x_currency_code,
            "bank" => $data->x_bank_name,
            "cardNumber" => $data->x_cardnumber,
            "quotas" => $data->x_quotas,
            "response" => $data->x_respuesta,
            "autorizacion" => $data->x_approval_code,
            "transactionId" => $data->x_transaction_id,
            "date" => $data->x_fecha_transaccion,
            "codeResponse" => $data->x_cod_respuesta,
            "responseReasonText" => $data->x_response_reason_text,
            "codTransactionState" => $data->x_cod_transaction_state,
            "status" => $data->x_transaction_state,
            "errorCode" => $data->x_errorcode,
            "franchise" => $data->x_franchise,
            "nameBusiness" => $data->x_business,
            "docType" => $data->x_customer_doctype,
            "document" => $data->x_customer_document,
            "name" => $data->x_customer_name,
            "lastName" => $data->x_customer_lastname,
            "email" => $data->x_customer_email,
            "phone" => $data->x_customer_phone,
            "phone" => $data->x_customer_movil,
            "indCountry" => $data->x_customer_ind_pais,
            "country" => $data->x_customer_country,
            "city" => $data->x_customer_city,
            "address" => $data->x_customer_address,
            "ip" => $data->x_customer_ip,
            "signature" => $data->x_signature,
            "testMode" => $data->x_test_request,
            "extra1" => $data->x_extra1,
            "extra2" => $data->x_extra2,
            "extra3" => $data->x_extra3,
            "extra4" => $data->x_extra4,
            "extra5" => $data->x_extra5,
            "extra6" => $data->x_extra6,
            "extra7" => $data->x_extra7,
            "extra7" => $data->x_extra8,
        ];
    }

    public function getEntities()
    {
        $data = MediosPago::select('Id', 'nombre')->where('tipo', 3)->where('activo', 1)->get();
        $response = [];

        if ($data) {
            foreach ($data as $value) {
                $response[] = [
                    "id" => $value->Id,
                    "name" => $value->nombre,
                ];
            }
        }

        return $response;

    }

    //////////////////////////// PROCESAR TRANSACCIONES Y SUSCRIPCIONES ///////////////////////////////////////////////////////////////////////////////

    public function getMasterCountries()
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                //CURLOPT_PORT => "1443",
                //CURLOPT_URL => "https://apilab.davivienda.com:1443/davivienda/publico/daviplata/v1/compra",
                CURLOPT_URL => "https://secure.payco.co/apprest/paises.json",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "accept: application/json",
                    "content-type: application/json",

                ),
            ));
            $resp = curl_exec($curl);
            curl_close($curl);
            return json_decode($resp);
        } catch (\Exception $exception) {
            return $exception;
        }
    }

    public function leerArchivoImagenesBase64($file, $clientId, $baseName = "document")
    {
        $data = explode(',', $file);
        $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');

        $name = explode("tmp/", $tmpfname);
        $name = $name[1];
        $sacarExt = explode('image/', $data[0]);
        if (count($sacarExt) == 1) {
            $sacarExt[1] = $sacarExt[0];
            $sacarExt[0] = "pdf";
        }
        if (isset($data[1])) {
            $validFormat = explode(';', $sacarExt[1]);
            $sacarExt[0] = $validFormat[0];
            if ($validFormat[0] != "jpg" && $validFormat[0] != "jpeg" && $validFormat[0] !== "png") {
                $success = false;
                $title_response = 'file format not allowed';
                $text_response = 'file format not allowed';
                $last_action = 'file format not allowed';
                $data = [];
                $arr_respuesta['success'] = $success;
                $arr_respuesta[CommonText::TITLE_RESPONSE] = $title_response;
                $arr_respuesta[CommonText::TEXT_RESPONSE] = $text_response;
                $arr_respuesta[CommonText::LAST_ACTION] = $last_action;
                $arr_respuesta['data'] = $data;
                $arr_respuesta['dataInterna'] = [];

                return $arr_respuesta;
            }
        }

        $base64 = base64_decode(isset($data[1]) ? $data[1] : $data[0]);
        file_put_contents(
            $tmpfname . "." . $sacarExt[0],
            $base64
        );
        unlink($tmpfname);

        $fechaActual = new \DateTime('now');

        //Subir los archivos
        $nameFile = "{$clientId}_{$baseName}_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";

        $success = true;
        $title_response = 'file format allowed';
        $text_response = 'file format allowed';
        $last_action = 'format allow valid';
        $data = [];
        $arr_respuesta['success'] = $success;
        $arr_respuesta[CommonText::TITLE_RESPONSE] = $title_response;
        $arr_respuesta[CommonText::TEXT_RESPONSE] = $text_response;
        $arr_respuesta[CommonText::LAST_ACTION] = $last_action;
        $arr_respuesta['data'] = $data;
        $arr_respuesta['dataInterna'] = [
            "location" => $tmpfname,
            "ext" => $sacarExt[0],
            "name" => $nameFile,
        ];

        return $arr_respuesta;
    }

    public function removeFileLocal($location, $extension)
    {
        unlink($location . "." . $extension);
    }

    public function modifyStock($value, $cantidad_anterior, $id)
    {
        $cantidad_actual = $value;
        $cantidad_guardar = $cantidad_anterior - $cantidad_actual;

        $search = new Search();
        $search->setSize(5000);
        $search->setFrom(0);
        $search->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
        $productResult = $this->searchGeneralElastic(["indice" => "producto", "data" => $search->toArray()]);
        $in_stock = $productResult["data"]->hits->hits[0]->_source->cantidad;

        $productoDataId = $productResult["data"]->hits->hits[0]->_id;
        $in_stock += $cantidad_guardar;

        $catalogoproductos = new CatalogoProductos();
        $catalogoproductos->cantidad = $in_stock;
        //actualizo en elastic search la nueva cantidad
        $anukisResponse = $this->elasticUpdatebyDocument([
            "indice" => "producto",
            "documentId" => $productoDataId,
            "data" => ["doc" => $catalogoproductos->toArray()],
        ]);

        return ($anukisResponse["success"]) ? true : false;

    }

    /////////////////////////////// Elastic Search //////////////////////////////////////////////////////////////////////
    public function authenticationApify($publicKey, $privateKey)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, getenv("BASE_URL_APIFY") . "/login");
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10000); //timeout in seconds

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($publicKey . ":" . $privateKey),
            )
        );

        $response = curl_exec($ch);

        curl_close($ch);
        $arrRequests = explode("\r\n\r\n", $response);

        $body = end($arrRequests);

        $header_code = '500';
        $header = $this->get_headers_from_curl_response($response);
        if ($header) {
            $code_header = ($header[0]['http_code']);
            $exp_header = explode(" ", $code_header);

            if (is_array($exp_header)) {
                if (isset($exp_header[1])) {
                    $header_code = $exp_header[1];
                }
            }
            return array('header_code' => $header_code, 'body' => $body, 'url' => "login");
        } else {
            return array('header_code' => $header_code, 'body' => $body, 'url' => "login");
        }
    }

    private function getTokenApifyPrivate()
    {
        if (!Cache::get("apify_private_jwt")) {
            $this->loginApifyPrivate();
            return true;
        }
        $this->tokenApifyPrivate = Cache::get("apify_private_jwt");
    }

    private function getTokenApifyPrivateElogistica()
    {
        if (!Cache::get("apify_private_elogistica_jwt")) {
            $this->loginElogistica();
        }
        return $this->tokenApifyPrivateElogisita;
    }

    public function loginElogistica()
    {

        $llavesBabilonia = BblClientesPasarelas::where("cliente_id", env("CLIENT_ID_APIFY_PRIVATE"))->where('estado', true)->first();

        $url = config("app.BASE_URL_ELOGISTICA") . "/login";

        $basic = base64_encode("{$llavesBabilonia->public_key}:{$llavesBabilonia->private_key}");
        $headers = ['Accept' => 'application/json', "authorization" => "Basic {$basic}"];
        $responseAuth = Requests::post($url, $headers, [], ["timeout" => 120]);

        $bearerToken = "";
        if ($responseAuth->status_code == "200" || $responseAuth->status_code == "100") {
            $body = json_decode($responseAuth->body, true);
            if (empty($body) || !isset($body["api_token"])) {
                $intentos = 0;
                //Se realizan 5 intentos maximos por si el login no responde la primera vez
                while ($bearerToken === "" && $intentos <= 5) {
                    $auth = Requests::post($url, $headers, []);
                    if ($auth->status_code == "200" || $auth->status_code == "100") {
                        $body = json_decode($responseAuth->body, true);
                        if (!empty($body) && isset($body["api_token"])) {
                            $bearerToken = $body["api_token"];
                        }
                    }
                    $intentos++;
                }
            } else {
                $bearerToken = $body["api_token"];
            }
        }
        Cache::put("apify_private_elogistica_jwt", $bearerToken, time() + (60 * 60));
        $this->tokenApifyPrivateElogisita = $bearerToken;
    }

    public function loginApify($public_key, $private_key)
    {

        $url = config('app.BASE_URL_APIFY') . "/login";

        $basic = base64_encode("{$public_key}:{$private_key}");
        $headers = ['Accept' => 'application/json', "authorization" => "Basic {$basic}"];

        $responseAuth = Requests::post($url, $headers, [], ["timeout" => 120]);

        $bearerToken = "";
        if ($responseAuth->status_code == "200" || $responseAuth->status_code == "100") {
            $body = json_decode($responseAuth->body, true);
            if (empty($body) || !isset($body["token"])) {
                $intentos = 0;
                //Se realizan 5 intentos maximos por si el login no responde la primera vez
                while ($bearerToken === "" && $intentos <= 5) {
                    $auth = Requests::post($url, $headers, []);
                    if ($auth->status_code == "200" || $auth->status_code == "100") {
                        $body = json_decode($responseAuth->body, true);
                        if (!empty($body) && isset($body["token"])) {
                            $bearerToken = $body["token"];
                        }
                    }
                    $intentos++;
                }
            } else {
                $bearerToken = $body["token"];
            }
        }

        Cache::put("apify_jwt", $bearerToken, time() + (60 * 60));
        $this->tokenApify = $bearerToken;
    }

    public function loginApifyPrivate($typeLogin = null)
    {
        $llavesBabilonia = BblClientesPasarelas::where("cliente_id", env("CLIENT_ID_BABILONIA"))->where('estado', true)->first();

        $url = getenv("BASE_URL_APIFY_PRIVATE") . "/login";

        $basic = base64_encode("{$llavesBabilonia->public_key}:{$llavesBabilonia->private_key}");
        $headers = ['Accept' => 'application/json', "authorization" => "Basic {$basic}"];
        if ($typeLogin == "elogistica") {
            $headers["X-elogistica"] = "apply";
        }
        $responseAuth = Requests::post($url, $headers, [], ["timeout" => 120]);

        $bearerToken = "";
        if ($responseAuth->status_code == "200" || $responseAuth->status_code == "100") {
            $body = json_decode($responseAuth->body, true);
            if (empty($body) || !isset($body["token"])) {
                $intentos = 0;
                //Se realizan 5 intentos maximos por si el login no responde la primera vez
                while ($bearerToken === "" && $intentos <= 5) {
                    $auth = Requests::post($url, $headers, []);
                    if ($auth->status_code == "200" || $auth->status_code == "100") {
                        $body = json_decode($responseAuth->body, true);
                        if (!empty($body) && isset($body["token"])) {
                            $bearerToken = $body["token"];
                        }
                    }
                    $intentos++;
                }
            } else {
                $bearerToken = $body["token"];
            }
        }
        if ($typeLogin == "elogistica") {
            Cache::put("apify_private_elogistica_jwt", $bearerToken, time() + (60 * 60));
            $this->tokenApifyPrivateElogisita = $bearerToken;
        } else {
            Cache::put("apify_private_jwt", $bearerToken, time() + (60 * 60));
            $this->tokenApifyPrivate = $bearerToken;
        }

    }

    public function createInvoiceCollection($body, $bulk = false)
    {
        $url = (!$bulk ? "/recaudo/factura/crear" : "/elastic/bulk/upload");
        $response = $this->consultElasticV2($body, $url);
        $logElastic = new LogElasticRecaudoFacturas();
        $logElastic->data = json_encode($response);
        if ($response->status_code === 401) {
            $this->countLogin++;
            if ($this->countLogin < 2) {
                $this->createInvoiceCollection($body, $bulk);
            } else {
                return false;
            }
        } else {
            return ($response->status_code === 200 || $response->status_code === 100);
        }
    }

    // para crear segun el indice ES
    public function createProductoCollectionElastic($body)
    {
        $response = $this->consultElasticV2($body, "/catalogue/producto/crear");
        $logElastic = new LogElasticRecaudoFacturas();
        $logElastic->data = json_encode($response);
        if ($response->status_code === 401) {
            $this->countLogin++;
            if ($this->countLogin < 2) {
                $this->createInvoiceCollection($body);
            } else {
                return false;
            }
        } else {
            return ($response->status_code === 200 || $response->status_code === 100);
        }
    }
    public function subirBulkElastic($body, $url)
    {
        $indice = isset($body["indice"]) ? $body["indice"] : "";
        $data = isset($body["data"]) ? $body["data"] : "";
        $newData = "\n";
        $stdclass = new \stdClass();
        $stdclass->index = new \stdClass();
        $stdclass->index->_index = $indice;
        if ($indice == 'recaudo_facturas') {
            $stdclass->index->_type = $indice;
        }
        //
        foreach ($data as $key => $dat) {
            $stdclass->index->_id = $dat["id"];
            $newData .= json_encode($stdclass) . "\n";
            $newData .= json_encode($data[$key]) . "\n";
        }
        $url = getenv("ELASTIC_SCHEME") . "://" . getenv("ELASTIC_HOST") . ":" . getenv("ELASTIC_PORT") . "/{$indice}/_bulk?pretty";

        $port = env("ELASTIC_PORT");

        return $this->elasticCurl($url, $newData, $port, "POST", getenv("ELASTIC_AUTHENTICATION"));
    }

    public function elasticBulkUploadConex($body)
    {
        $response = $this->subirBulkElastic($body, "/elastic/bulk/upload");
        $response = $response->getData(true);

        if ($response["status_code"] === 200 || $response["status_code"] === 100) {

            return array("success" => true, "items" => $response);
        } else {
            return array("success" => false, "items" => null);
        }
    }

    public function elasticBulkUpload($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/bulk/upload");

        //todo: Crear tabla para logs de catalogo
        // $logElastic = new LogElasticRecaudoFacturas();
        // $logElastic->data = json_encode($response);
        // $logElastic->save();
        if ($response->status_code === 401) {
            $this->countLogin++;
            if ($this->countLogin < 2) {
                $this->createInvoiceCollection($body);
            } else {
                return false;
            }
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {
                $responseBody = json_decode($response->body);
                return array("success" => true, "items" => $responseBody->items);
            } else {
                return array("success" => false, "items" => null);
            }
        }
    }

    public function updateInvoiceCollection($body)
    {
        $response = $this->consultElasticV2($body, "/recaudo/factura/actualizar");
        $logElastic = new LogElasticRecaudoFacturas();
        $logElastic->data = json_encode($response);
        // $logElastic->save();
        if ($response->status_code === 401) {
            $this->countLogin++;
            if ($this->countLogin < 2) {
                $this->createInvoiceCollection($body);
            } else {
                return false;
            }
        } else {
            return ($response->status_code === 200 || $response->status_code === 100);
        }
    }

    public function elasticUpdateConex($body)
    {
        $indice = $body["indice"];
        if (isset($body["indice"])) {
            unset($body["indice"]);
            if (isset($body["clientId"])) {
                unset($body["clientId"]);
            }

            if (count($body) <= 0) {
                $body = '';
            } else {
                $body = json_encode($body);
            }

        } else {
            $body = json_encode($body);
        }

        $url = getenv("ELASTIC_SCHEME") . "://" . getenv("ELASTIC_HOST") . ":" . getenv("ELASTIC_PORT") . "/{$indice}/_update_by_query?refresh&conflicts=proceed";
        $port = env("ELASTIC_PORT");

        return $this->elasticCurl($url, $body, $port, "POST", getenv("ELASTIC_AUTHENTICATION"));
    }

    public function elasticUpdate($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/update");
        if ($response->status_code === 401) {
            $this->countLogin++;
            if ($this->countLogin < 2) {
                $this->createInvoiceCollection($body);
            } else {
                return false;
            }
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {
                return array("success" => true, "data" => $response);
            } else {
                return array("success" => false, "data" => []);
            }
        }
    }

    private function consultElasticV2($query, $url)
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        $url = config('app.BASE_URL_ANUKIS') . $url;
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json', "authorization" => $authorization];
        $data = json_encode($query);
        return Requests::post($url, $headers, $data, ["timeout" => 120]);
    }

    public function elasticRegister($query, $count)
    {
        $indice = $query["indice"];
        $body = $query;

        if ($body) {

            if (isset($body["indice"])) {
                unset($body["indice"]);
                if (isset($body["clientId"])) {
                    unset($body["clientId"]);
                }

                if (count($body) <= 0) {
                    $body = '';
                } else {
                    $body = json_encode($body);
                }

            } else {
                $body = json_encode($body);
            }
        }

        $param = ($count ? "_count" : "_search");
        $url = getenv("ELASTIC_SCHEME") . "://" . getenv("ELASTIC_HOST") . ":" . getenv("ELASTIC_PORT") . "/$indice/{$param}";

        $port = getenv("ELASTIC_PORT");

        return $this->elasticCurl($url, $body, $port, "POST", getenv("ELASTIC_AUTHENTICATION"));
    }

    public function consultElasticSearchConex($query, $index, $count = false)
    {
        $query["indice"] = $index;
        $request = $this->elasticRegister($query, $count);
        $body = $request->getData(true);

        if ($body["status_code"] === 200 || $body["status_code"] === 100) {

            if ($count && isset($body["count"])) {
                return ["status" => true, 'pagination' => null, "data" => $body["count"], "message" => "Consulta a elasticsearch exitosa count"];
            }
            if (isset($body["hits"]["total"]["value"])) {
                $data = [];
                foreach ($body["hits"]["hits"] as $value) {
                    if (isset($value["inner_hits"])) {
                        array_push($data, $value);
                    } else {
                        array_push($data, $value["_source"]);
                    }
                }

                $totalCount = 0;
                if (isset($body["aggregations"]) && isset($body["aggregations"]["data"])) {

                    $totalCount = $body["aggregations"]["data"]["doc_count"];
                } elseif (isset($body["aggregations"]) && isset($body["aggregations"]["total"])) {

                    $totalCount = $body["aggregations"]["total"]["buckets"]["total"]["doc_count"];
                } else {

                    $totalCount = $body["hits"]["total"]["value"];
                }

                !isset($query['from']) ? $query['from'] = 1 : 0;

                $paginacion = ["totalCount" => $totalCount,
                    "limit" => isset($query['size']) ? $query['size'] : 10, "page" => $query["from"] + 1];

                return ["status" => true,
                    'pagination' => $paginacion,
                    "data" => $data,
                    "aggregations" => isset($body["aggregations"]) ? $body["aggregations"] : null,
                    "message" => "Consulta a elasticsearch exitosa",
                ];
            }
        } else {
            return ["status" => false, "data" => [], "message" => "Error consultando los registros"];
        }

        return ["status" => false, "data" => [], "message" => "Error consultando los registros"];
    }

    public function consultElasticSearch($query, $index, $count = false)
    {
        $query["indice"] = $index;
        $url = ($count ? "/elastic/count/register" : "/elastic/consult/register");
        $request = $this->consultElasticV2($query, $url);
        if ($request->status_code === 200 || $request->status_code === 100) {
            $body = json_decode($request->body);
            if ($count && isset($body->count)) {
                return ["status" => true, 'pagination' => null, "data" => $body->count, "message" => "Consulta a elasticsearc exitosa count"];
            }
            if (isset($body->hits->total->value)) {

                $data = [];
                foreach ($body->hits->hits as $value) {
                    if (isset($value->inner_hits)) {
                        array_push($data, $value);
                    } else {
                        array_push($data, $value->_source);
                    }
                }
                $totalCount = 0;
                if (isset($body->aggregations) && isset($body->aggregations->data)) {
                    $totalCount = $body->aggregations->data->doc_count;
                } elseif (isset($body->aggregations) && isset($body->aggregations->total)) {
                    $totalCount = $body->aggregations->total->buckets->total->doc_count;
                } else {
                    $totalCount = $body->hits->total->value;
                }
                $paginacion = ["totalCount" => $totalCount,
                    "limit" => isset($query['size']) ? $query['size'] : 10, "page" => $query["from"] + 1];

                return ["status" => true,
                    'pagination' => $paginacion,
                    "data" => $data,
                    "aggregations" => isset($body->aggregations) ? $body->aggregations : null,
                    "message" => "Consulta a elasticsearc exitosa",
                ];
            }
        } else {
            return ["status" => false, "data" => [], "message" => "Error consultando los registros"];
        }

        return ["status" => false, "data" => [], "message" => "Error consultando los registros"];
    }

    public function elasticGeneralBulkUpload($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/bulkraw/upload");
        if ($response->status_code === 401) {
            $this->elasticBulkUpload($body);
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {
                $responseBody = json_decode($response->body);
                return array("success" => true, "items" => $responseBody->items);
            } else {
                return array("success" => false, "items" => null);
            }
        }
    }

    public function subirBulkRawQuery($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/bulk/rawquery");
        if ($response->status_code === 401) {
            $this->elasticBulkUpload($body);
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {
                $responseBody = json_decode($response->body);
                return array("success" => true, "items" => $responseBody->items);
            } else {
                return array("success" => false, "items" => null);
            }
        }
    }

    public function elasticUpdatebyDocument($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/document/update");
        $logElastic = new LogElasticRecaudoFacturas();
        $logElastic->data = json_encode($response);
        // $logElastic->save();
        if ($response->status_code === 401) {
            $this->elasticBulkUpload($body);
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {
                $responseBody = json_decode($response->body);
                return array("success" => true, "data" => $responseBody);
            } else {
                return array("success" => false, "items" => null);
            }
        }
    }

    public function searchGeneralElastic($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/generalsearch");
        if ($response->status_code === 401) {
            $this->elasticBulkUpload($body);
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {

                $responseBody = json_decode($response->body);

                return array("success" => true, "data" => $responseBody);
            } else {
                return array("success" => false, "items" => null);
            }
        }
    }

    public function updateRawQueryElastic($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/update/rawquery");
        if ($response->status_code === 401) {
            $this->elasticBulkUpload($body);
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {
                $responseBody = json_decode($response->body);
                return array("success" => true, "data" => $responseBody);
            } else {
                return array("success" => false, "items" => null);
            }
        }
    }

    public function simpleSearchElastic($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/rawquery");
        if ($response->status_code === 401) {
            $this->elasticBulkUpload($body);
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {

                $responseBody = json_decode($response->body);

                return array("success" => true, "data" => $responseBody);
            } else {
                return array("success" => false, "items" => null);
            }
        }
    }

    public function searchRawQueryElastic($body)
    {
        $response = $this->consultElasticV2($body, "/elastic/rawquery");
        if ($response->status_code === 401) {
            $this->elasticBulkUpload($body);
        } else {
            if ($response->status_code === 200 || $response->status_code === 100) {
                $responseBody = json_decode($response->body);
                return array("success" => true, "data" => $responseBody);
            } else {
                return array("success" => false, "items" => null);
            }
        }
    }

    public function enviarEmailTicket($idTicket)
    {
        $baseUrlRest = getenv("BASE_URL_REST");
        $baseUrlAppRest = getenv("BASE_URL_APP_REST_ENTORNO");
        $url_email = "{$baseUrlRest}/{$baseUrlAppRest}/email/ticket/" . $idTicket;

        return $this->sendCurlVariables($url_email, [], "GET", true);
    }

    public function enviarEmailRespuestaTicket($idRespuestaTicket)
    {
        $baseUrlRest = getenv("BASE_URL_REST");
        $baseUrlAppRest = getenv("BASE_URL_APP_REST_ENTORNO");
        $updateTicketUrl = "{$baseUrlRest}/{$baseUrlAppRest}/email/ticket/update/" . $idRespuestaTicket;
        $envioEmailUrl = "{$baseUrlRest}/{$baseUrlAppRest}/email/ticket/respuesta/" . $idRespuestaTicket;
        $updateTicket = $this->sendCurlVariables($updateTicketUrl, [], "GET", true);
        $envioEmail = $this->sendCurlVariables($envioEmailUrl, [], "GET", true);
        $data = ["updateTicket" => $updateTicket, "envioEmail" => $envioEmail];

        return $data;
    }

    public function getAlliedEntity($clientId)
    {
        $alliedEntityId = null;
        $clientCreatedAt = null;
        $client = BblClientes::find($clientId);

        if (is_null($client)) {
            throw new GeneralException("Allied entity not exist");
        }
        return [
            "alliedEntityId" => env("CLIENT_ID_BABILONIA"),
            "clientCreatedAt" => $client->created_at->getTimestamp(),
        ];
    }

    public function elogisticaRequest($body, $path, $count = 0, $type = "POST", $encode = true)
    {
        $token = $this->getTokenApifyPrivateElogistica();
        $basicAuthEncode = base64_encode($token . ":");
        $headers = ['Accept' => 'application/json', "authorization" => "Basic {$basicAuthEncode}"];
        $url = config("app.BASE_URL_ELOGISTICA") . $path;
        if ($type == "POST") {
            $data = $encode ? json_encode($body) : $body;
            $response = Requests::post($url, $headers, $data, ['timeout' => 120, 'connect_timeout' => 120]);
        } elseif ($type == "GET") {
            $response = Requests::get($url, $headers, ['timeout' => 120, 'connect_timeout' => 120]);
        } else {
            $data = json_encode($body);
            $response = Requests::put($url, $headers, $data, ['timeout' => 120, 'connect_timeout' => 120]);
        }

        if ($response->status_code === 401) {
            if ($count > 2) {
                return array("success" => false, "data" => null);
            } else {
                return $this->elogisticaRequest($body, $path, $count + 1, $type);
            }
        } else {
            if ($response->status_code == 200 || $response->status_code == 100) {
                $responseBody = json_decode($response->body);
                // return array("success" => true, "data" => $responseBody->data, "debug" => json_encode($response));
                return (array) $responseBody;
            } else {
                return array("success" => false, "data" => null);
            }
        }
    }

    //CREATE CUSTOMER
    public function createCustomer($data)
    {

        $this->initSdkBbl();

        //CREATE CUSTOMER
        $customer = $this->epayco->customer->create(array(
            "token_card" => $data["token_card"],
            "name" => $data["name"],
            "last_name" => $data["last_name"],
            "email" => $data["email"],
            "default" => $data["default"],
            //Optional parameters: These parameters are important when validating the credit card transaction
            "city" => $data["city"],
            "address" => $data["address"],
            "phone" => $data["phone"],
            "cell_phone" => $data["cell_phone"],
        ));

        return $customer;
    }

    public function loginRestSuscripciones()
    {
        $headers = ['Accept' => 'application/json'];
        $data = [
            "public_key" => env('BBL_PUBLIC_KEY'),
            "private_key" => env('BBL_PRIVATE_KEY'),
        ];
        $url = config("app.BASE_URL_SUSCRIPCIONES") . "/v1/auth/login";
        $responseAuth = Requests::post($url, $headers, $data, ['timeout' => 120, 'connect_timeout' => 120]);
        $bearerToken = "";
        if ($responseAuth->status_code == "200" || $responseAuth->status_code == "100") {
            $body = json_decode($responseAuth->body, true);
            if (empty($body) || !isset($body["bearer_token"])) {
                $intentos = 0;
                //Se realizan 5 intentos maximos por si el login no responde la primera vez
                while ($bearerToken === "" && $intentos <= 5) {
                    $auth = Requests::post($url, $headers, $data, ['timeout' => 120, 'connect_timeout' => 120]);
                    if ($auth->status_code == "200" || $auth->status_code == "100") {
                        $body = json_decode($responseAuth->body, true);
                        if (!empty($body) && isset($body["bearer_token"])) {
                            $bearerToken = $body["bearer_token"];
                        }
                    }
                    $intentos++;
                }
            } else {
                $bearerToken = $body["bearer_token"];
            }
        }
        Cache::put("api_suscripciones_jwt", $bearerToken, time() + (60 * 60));
        $this->tokenApiSuscripciones = $bearerToken;

    }

    private function getTokenApiSuscripciones()
    {
        if (!Cache::get("api_suscripciones_jwt")) {
            $this->loginRestSuscripciones();
        }
        return $this->tokenApiSuscripciones;
    }

    public function customerUpdate($customerId, $data)
    {
        try {
            $customer = false;
            if (is_array($data)) {
                $token = $this->getTokenApiSuscripciones();
                $url = config("app.BASE_URL_SUSCRIPCIONES") . "/payment/v1/customer/edit/" . env('BBL_PUBLIC_KEY') . "/" . $customerId;
                $headers = ["authorization" => "Bearer {$token}"];
                $customerResponse = Requests::post($url, $headers, $data, ['timeout' => 120, 'connect_timeout' => 120]);
                if ($customerResponse->status_code == 200) {
                    $customer = json_decode($customerResponse->body);
                }
            }
            return $customer;
        } catch (\Exception $exception) {
            $error_response = [];
            $error_response['status'] = false;
            $error_response['success'] = false;
            $error_response['message'] = "Error registro customer";
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $error_response['data'] = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
            return (object) $error_response;
        }
    }
    public function addNewTokenBbl($data)
    {
        $customer = false;
        if (is_array($data)) {
            $this->initSdkBbl();
            $customer = $this->epayco->customer->addNewToken($data);

        }

        return $customer;
    }

    public function createSubscription($data)
    {
        $this->initSdkBbl();
        $client = BblClientes::find($data["clientId"]);

        $customer = $this->epayco->customer->get($client->cliente_sdk_id);

        $sub = $this->epayco->subscriptions->create(array(
            "id_plan" => $data["id_plan"],
            "customer" => $client->cliente_sdk_id,
            "token_card" => $customer->data->cards[0]->token,
            "doc_type" => $data["doc_type"],
            "doc_number" => $data["doc_number"],
            //Optional parameter: if these parameter it's not send, system get ePayco dashboard's url_confirmation
            "url_confirmation" => isset($data["url_confirmation"]) ? $data["url_confirmation"] : "",
            "method_confirmation" => isset($data["method_confirmation"]) ? $data["method_confirmation"] : "",
        ));

        return $sub;

    }

    public function cancelSubscription($suscripcion_id)
    {
        $this->initSdkBbl();

        $sub = $this->epayco->subscriptions->cancel($suscripcion_id);

        return $sub;

    }

    public function sendEmailCardSuscription($user, $card, $subject, $template)
    {
        $this->emailPanelRest(
            $subject,
            $user->email,
            $template,
            [
                "clientName" => $user->nombre . " " . $user->apellido,
                "cardFranchise" => $this->imgFranchise($card->name),
                "cardLastNumbers" => substr($card->mask, -4),
            ]
        );
    }

    public function imgFranchise($franchise)
    {
        $value = "";
        switch ($franchise) {
            case "american express":
                $value = 'american';
                break;
            case "visa":
                $value = 'visa';
                break;
            case "mastercard":
                $value = 'master';
                break;
            case "dinersclub":
                $value = 'diners';
                break;
            default:
                $value = 'master';
        }
        return $value;
    }

}
