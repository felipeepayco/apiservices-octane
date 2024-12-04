<?php

namespace App\Listeners\Customer\Validation;

use App\Events\Customer\Validation\ValidationCustomerEditSubDomainEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use Illuminate\Http\Request;

class ValidationCustomerEditSubDomainListener extends HelperPago
{

    private $validate;
    private $arr_respuesta = [];

    /**
     * ValidationCustomerEditSubDomainEvent constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->validate = new Validate();

    }

    /**
     * @param ValidationCustomerEditSubDomainEvent $event
     * @return array
     * @throws \Exception
     */
    public function handle(ValidationCustomerEditSubDomainEvent $event)
    {

        $params = $event->arr_parametros;
        //VALIDATE FIELDS
        if (!$this->validate->ValidateVacio($params["sub_domain"])) {

            $this->validate->setError(500, "the sub_domain field is invalid, value expected");
        }

        if (!$this->isValidSubdomain($params["sub_domain"])) {
            $this->validate->setError(500, "the sub_domain is invalid");

        }

        //VALIDATE FORBIDDEN SUB DOMAINS
        $forbidden_domains = ["apify-private-bbl", "anukis-bbl", "conex-bbl", "khepri-bbl", "dashboard-bbl"];

        if (in_array($params["sub_domain"], $forbidden_domains)) {
            $this->validate->setError(500, "the sub_domain field is invalid, please choose another subdomain name");

        }


        $url = config('app.BASE_URL_BBL');
        $selectedSubdomain = $params["sub_domain"];

        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['host'])) {
            $host = $parsedUrl['host'];
            $newUrl = $parsedUrl["scheme"] . '://' . $selectedSubdomain.".".$host;
        }

        $sub_domain_exist = BblClientes::where('url', $newUrl)->first();

        if (!empty($sub_domain_exist)) {

            if ($sub_domain_exist->id != $params["clientId"]) {
                //THIS MEAN THE SUBDOMAIN DOESN'T BELONG TO THE CLIENT
                $this->validate->setError(500, "the selected sub_domain is in use, please choose another subdomain name");

            }

        }

        if ($this->validate->totalerrors > 0) {

            $success = false;
            $last_action = 'validation data save';
            $title_response = 'Error';
            $text_response = 'Some fields are required, please correct the errors and try again';

            $data = [
                'totalErrors' => $this->validate->totalerrors,
                'errors' => $this->validate->errorMessage,
            ];
            $response = [
                'success' => $success,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
            ];

            return $response;
        }

        $this->arr_respuesta['success'] = true;

        return $this->arr_respuesta;
    }

    public function isValidSubdomain($name)
    {
        $pattern = '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i';

        return preg_match($pattern, $name);
    }

}
