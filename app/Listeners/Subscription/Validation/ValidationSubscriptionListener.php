<?php

namespace App\Listeners\Subscription\Validation;

use App\Events\Subscription\Validation\ValidationSubscriptionEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Helpers\Validation\CommonValidation;
use App\Helpers\Messages\CommonText;
use Illuminate\Http\Request;

class ValidationSubscriptionListener extends HelperPago
{

    //TABLE FIELDS

    private $validate;

    private $params = null;

    /**
     * constructor.
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
    public function handle(ValidationSubscriptionEvent $event)
    {

        $this->params = $event->arr_parametros;
        $this->params['success'] = true;
        return $this->params;
    }

}
