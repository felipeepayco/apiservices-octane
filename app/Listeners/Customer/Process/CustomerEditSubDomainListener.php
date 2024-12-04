<?php

namespace App\Listeners\Customer\Process;

use App\Events\Customer\Process\CustomerEditSubDomainEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Models\BblClientes;
use Illuminate\Http\Request;

class CustomerEditSubDomainListener extends HelperPago
{
    private $arr_respuesta = [];

    /**
     * CustomerNewListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param CustomerEditSubDomainEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(CustomerEditSubDomainEvent $event)
    {

        $params = $event->arr_parametros;

        $bbl_client = BblClientes::find($params["clientId"]);

        $url = config('app.BASE_URL_BBL');
        $selectedSubdomain = $params["sub_domain"];

        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['host'])) {
            $host = $parsedUrl['host'];
            $newUrl = $parsedUrl["scheme"] . '://' . $selectedSubdomain.".".$host;
        }

        $bbl_client->url = $newUrl;
        $bbl_client->save();

        if ($bbl_client) {

            $this->arr_respuesta['success'] = true;
            $this->arr_respuesta['status'] = 200;
            $this->arr_respuesta['message'] = "Sub domain updated successfully";
            $this->arr_respuesta['data'] = $bbl_client->url;
        } else {
            $this->arr_respuesta['success'] = false;
            $this->arr_respuesta['status'] = 422;
            $this->arr_respuesta['message'] = "There was an error updating your subdomain, please try again later";
            $this->arr_respuesta['data'] = [];
        }

        return $this->arr_respuesta;

    }

}
