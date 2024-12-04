<?php namespace App\Helpers\Pago;

use App\Http\Controllers\Controller as Controller;
use App\Models\LlavesClientes;
use Illuminate\Http\Request;


class HelperSubscriptions extends Controller
{

    public $request;
    private $public_key;
    private $private_key;

    public function __construct(Request $request,$clientId)
    {
        $this->request = $request;
        $this->getKeyClient($clientId);
    }

    private function getKeyClient($clientId)
    {
        $llaves = LlavesClientes::where('cliente_id', $clientId)->first();
        if ($llaves) {
            $this->private_key = $llaves->private_key_decrypt;
            $this->public_key = $llaves->public_key;
        }
        return $llaves;
    }

    public function authentication()
    {
        $data = array(
            'public_key' => $this->public_key,
            'private_key' => $this->private_key
        );
        $json = json_encode($data);
        $curl = curl_init();

        $baseUrl = env("BASE_URL_API_SUBSCRIPTIONS");

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$baseUrl}/v1/auth/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "type: sdk-jwt",
                "Accept: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);

    }

    private function get_headers_from_curl_response($headerContent)
    {

        $headers = array();
        $arrRequests = explode("\r\n\r\n", $headerContent);
        for ($index = 0; $index < count($arrRequests) - 1; $index++) {

            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line) {
                if ($i === 0)
                    $headers[$index]['http_code'] = $line;
                else {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }

        return $headers;
    }

    public function request($url, $data, $typePetition, $json = true, $bearerToken){
        $postvars = '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        if ($typePetition == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if (!$json) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10000);

        if ($json) {
            $varEncode = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $varEncode);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($varEncode),
                    'Authorization: Bearer ' . $bearerToken)
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

    public function deleteTokenCustomer($data)
    {
        $deleteTokenMongoDb = false;
        if (is_array($data)) {
            $authentication = $this->authentication();
            if($authentication->status){

                $deleteTokenMongoDb = "";
            }
        }

        return $deleteTokenMongoDb;

    }

}
