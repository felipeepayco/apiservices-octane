<?php

namespace App\Listeners\Subscription\Validation;

use App\Events\Subscription\Validation\ValidationSubscriptionNewEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ValidationSubscriptionNewListener extends HelperPago
{

    //TABLE FIELDS
    const empty = 'empty';
    const ID_PLAN = "id_plan";
    const DOC_TYPE = "doc_type";
    const DOC_NUMBER = "doc_number";
    const URL_CONFIRMATION = "url_confirmation";
    const METHOD_CONFIRMATION = "method_confirmation";

    private $validate;

    private $arr_respuesta = [];
    private $params = null;

    /**
     * ValidationCustomerNewListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->validate = new Validate();

    }

    /**
     * @return array
     * @throws \Exception
     */
    public function handle(ValidationSubscriptionNewEvent $event)
    {

        $this->params = $event->arr_parametros;

        //VALIDATE FIELDS
        //PLAN
        #if (!$this->validate->ValidateVacio($this->params[self::ID_PLAN])) {$this->validate->setError(500, "the " . self::ID_PLAN . " attribute cannot be empty, string expected");}

        //VALIDATE DOCUMENTS
        if ($this->validate->validateLength($this->params[self::DOC_TYPE], 20)) {$this->validate->setError(500, "the " . self::DOC_TYPE . " is too long, please add a valid value");}
        if (!$this->validate->ValidateDocumentType($this->params[self::DOC_TYPE])) {$this->validate->setError(500, "the " . self::DOC_TYPE . " attribute is invalid,please add a valid one");}
        if ($this->validate->validateLength($this->params[self::DOC_NUMBER], 20)) {$this->validate->setError(500, "the " . self::DOC_NUMBER . " is too long, please add a valid value");}
        if (!$this->validate->ValidateDocument($this->params[self::DOC_TYPE], $this->params[self::DOC_NUMBER])) {$this->validate->setError(500, "the " . self::DOC_NUMBER . " attribute is invalid, please add a valid identification");}

        //OPTIONAL VALUES
        //VALIDATE URL
        if (!empty($this->params[self::URL_CONFIRMATION])) {

            if (empty($this->params[self::METHOD_CONFIRMATION])) {
                $this->validate->setError(500, "the " . self::METHOD_CONFIRMATION . " attribute cannot be empty");
            }
            if (!$this->validate->ValidateUrl($this->params[self::URL_CONFIRMATION])) {$this->validate->setError(500, "the domain is invalid,please verify that the url is correct");}
        }

        //VALIDATE METHOD
        if (!empty($this->params[self::METHOD_CONFIRMATION])) {

            if (empty($this->params[self::URL_CONFIRMATION])) {
                $this->validate->setError(500, "the " . self::URL_CONFIRMATION . " attribute cannot be empty");
            }
            if (!$this->validate->validateHttpMethod($this->params[self::METHOD_CONFIRMATION], 'POST', 'GET')) {$this->validate->setError(500, "the method is invalid,please select a valid one");}
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

            $this->saveLog(2, $this->params["clientId"], '', $response, 'customer_new');

            return $response;
        }

        $this->arr_respuesta['success'] = true;

        return $this->arr_respuesta;
    }

}
