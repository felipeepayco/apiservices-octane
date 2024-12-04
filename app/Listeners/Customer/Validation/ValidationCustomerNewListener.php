<?php

namespace App\Listeners\Customer\Validation;

use App\Events\Customer\Validation\ValidationCustomerNewEvent;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Listeners\Services\VendeConfigPlanService;
use Illuminate\Http\Request;
use App\Helpers\Edata\HelperEdata;
use HelperEdata as GlobalHelperEdata;
use App\Helpers\Messages\CommonText as CM;



class ValidationCustomerNewListener extends HelperPago
{
    const EMPTY = 'empty';
    const NAME="name";
    const LAST_NAME="last_name";
    const EMAIL="email";
    const CITY="city";
    const ADDRESS="address";
    const PHONE="phone";
    const CELLPHONE="cellphone";
    const TOKEN_CARD="token_card";

    private $validate;
    private $arr_respuesta=[];

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
     * @param ValidationCustomerNewEvent $event
     * @return array
     * @throws \Exception
     */
    public function handle(ValidationCustomerNewEvent $event)
    {

        $params=$event->arr_parametros;
        //VALIDATE FIELDS
        $this->validateNotEmpty($params["token_card"],self::TOKEN_CARD);
        $this->validateOnlyLetters($params["name"],self::NAME);
        $this->validateOnlyLetters($params["last_name"],self::LAST_NAME);
        $this->validateEmail($params["email"],self::EMAIL);

        if ($this->validate->totalerrors > 0) {

            $success        = false;
            $last_action    = 'validation data save';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data = [
                'totalErrors' => $this->validate->totalerrors,
                'errors'      => $this->validate->errorMessage,
            ];
            $response = [
                'success'        => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data,
            ];

            $this->saveLog(2,$params["clientId"], '', $response, 'customer_new');

            return $response;
        }

        $this->arr_respuesta['success']= true;

        return $this->arr_respuesta;
    }

    private function validateNotEmpty($string,$column)
    {
         if($string=="")
         {
            $this->validate->setError(500,"the {$column} attribute cannot be empty, string expected");

         }
        
    }

    private function validateOnlyLetters($string,$column)
    {
         $this->validateNotEmpty($string,$column);

         if (!preg_match('/^[\p{L} ]+$/u', $string))
         {
            $this->validate->setError(500,"the {$column} attribute is invalid, numbers are not allowed");
         }
        
    }


    private function validateEmail($email,$column)
    {
        if($email=="")
        {
           $this->validate->setError(500,"the {$column} attribute cannot be empty, string expected");

        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
           
            $this->validate->setError(500,"Invalid email format");
        }
    }
 
}
