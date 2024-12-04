<?php

namespace App\Http\Controllers;

use App\Models\LogRest;
use App\Models\LogGeoip;
use App\Models\PasarelaConfig;

use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use WpOrg\Requests\Requests;


class HerramientasController extends HelperPago
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


    public function getIpClient()
    {

        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe

                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    } else {
                        return "ip invalida";
                    }
                }
            }
        }
    }






    private function listaNegra($ip)
    {
        $ips = array("127.0.0.1", "0.0.0.0", "::1");
        return (in_array($ip, $ips)) ? true : false;
    }

}
