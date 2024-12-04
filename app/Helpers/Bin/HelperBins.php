<?php

namespace App\Helpers\Bin;

use App\Http\Controllers\Controller as Controller;

class HelperBins extends Controller
{
    public static string $FOLDER_BIN = "/bin/";

    public static function sandboxBin($url, $typeRequest)
    {
        $apiKey = env("API_KEY_BIN_LOOKUP");
        $headers = [
            'x-api-key: ' . $apiKey,
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $typeRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $output = json_decode(curl_exec($ch), 1);
        curl_close($ch);
        return $output;
    }

    public static function getBin($bin) {
        return HelperBins::sandboxBin(env("URL_BIN_LOOKUP").HelperBins::$FOLDER_BIN.$bin, 'GET');
    }
}