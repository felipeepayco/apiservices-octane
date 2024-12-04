<?php namespace App\Http\Lib;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Utils
 *
 * @author Daniel Quiroz
 */
class Utils
{

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
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        if ($tipo_envio == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            if (!$json) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
            }//0 for a get request
        }

        if ($this->autenticacion == true) {

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password)));
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
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($varEncode))
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

    private function get_headers_from_curl_response($headerContent)
    {

        $headers = array();

        // Split the string on every "double" new line.
        $arrRequests = explode("\r\n", $headerContent);
        // Loop of response headers. The "count() -1" is to 
        //avoid an empty row for the extra line break before the body of the response.
        for ($index = 0; $index < count($arrRequests) - 1; $index++) {

            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line) {
                if ($i === 0)
                    $headers[$index]['http_code'] = $line;
                else {
                    list ($key, $value) = explode(': ', $line);
                    if (isset($headers[$index])) {
                        $headers[$index][$key] = $value;
                    }
                }
            }
        }

        return $headers;
    }

    public function geoip()
    {

        try {
            $ip = $this->getRealIP();
            $url1 = 'http://vpn.payco.co/apprest/geoip/json/';
            $file = file_get_contents($url1 . $ip);
            $data = json_decode($file);
        } catch (\Exception $e) {

            $data = NULL;
        }
        return $data;
    }

    public function getRealIP()
    {

        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        } else {
            return $_SERVER["REMOTE_ADDR"];
        }

    }

    public function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function sanitize($string)
    {
        $string = ltrim($string);
        $string = rtrim($string);
        $string = substr($string, 0,249);

        $string = str_replace(
            array('Ã¡', 'Ã ', 'Ã¤', 'Ã¢', 'Âª', 'Ã?', 'Ã€', 'Ã‚', 'Ã„'), array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'), $string
        );

        $string = str_replace(
            array('Ã©', 'Ã¨', 'Ã«', 'Ãª', 'Ã‰', 'Ãˆ', 'ÃŠ', 'Ã‹'), array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'), $string
        );

        $string = str_replace(
            array('Ã­', 'Ã¬', 'Ã¯', 'Ã®', 'Ã?', 'ÃŒ', 'Ã?', 'ÃŽ'), array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'), $string
        );

        $string = str_replace(
            array('Ã³', 'Ã²', 'Ã¶', 'Ã´', 'Ã“', 'Ã’', 'Ã–', 'Ã”'), array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'), $string
        );

        $string = str_replace(
            array('Ãº', 'Ã¹', 'Ã¼', 'Ã»', 'Ãš', 'Ã™', 'Ã›', 'Ãœ'), array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'), $string
        );

        $string = str_replace(
            array('Ã±', 'Ã‘', 'Ã§', 'Ã‡'), array('n', 'N', 'c', 'C',), $string
        );

        $string = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $string
        );

        $string = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $string
        );

        $string = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $string
        );

        $string = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $string
        );

        $string = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $string
        );

        $string = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C',),
            $string
        );

        //Esta parte se encarga de eliminar cualquier caracter raro
        return str_replace(
            array("\\", "Â¨", "Âº", "~",
                "@", "|", "!", "\"",
                "Â·", "$", "%", "&", "/",
                "(", ")", "?", "'", "Â¡",
                "Â¿", "[", "^", "`", "]",
                "+", "}", "{", "Â¨", "Â´",
                ">", "< ", ";", ",", ":", "×",
            ), '_', $string
        );
    }


}
