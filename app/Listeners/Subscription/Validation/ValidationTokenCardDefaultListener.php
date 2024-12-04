<?php

namespace App\Listeners\Subscription\Validation;

use App\Events\Subscription\Validation\ValidationTokenCardDefaultEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Helpers\Validation\CommonValidation;
use App\Helpers\Messages\CommonText;
use Illuminate\Http\Request;

class ValidationTokenCardDefaultListener extends HelperPago
{

    //TABLE FIELDS
    const CUSTOMERID = 'customerId';
    const TOKEN = "token";
    const FRANCHISE = "franchise";
    const MASK = "mask";

    private $validate;

    private $params = null;

    /**
     * ValidationTokenCardDefaultListener constructor.
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
    public function handle(ValidationTokenCardDefaultEvent $event)
    {

        $this->params = $event->arr_parametros;
        $customerId = CommonValidation::validateIsSet($this->params,self::CUSTOMERID,null,'string');
        $token = CommonValidation::validateIsSet($this->params, self::TOKEN, null, 'string');
        $franchise = CommonValidation::validateIsSet($this->params, self::FRANCHISE, null, 'string');
        $mask = CommonValidation::validateIsSet($this->params, self::MASK, null, 'string');

        CommonValidation::validateParamFormat(
          $this->params,$this->validate,$customerId,
          self::CUSTOMERID,CommonText::EMPTY
        );
        CommonValidation::validateParamFormat($this->params,$this->validate,$token,self::TOKEN,CommonText::EMPTY);
        CommonValidation::validateParamFormat(
          $this->params,$this->validate,$franchise,
          self::FRANCHISE,CommonText::EMPTY
        );
        CommonValidation::validateParamFormat($this->params,$this->validate,$mask,self::MASK,CommonText::EMPTY);

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

        $this->params['success'] = true;

        return $this->params;
    }

}
