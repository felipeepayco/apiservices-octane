<?php

namespace App\Events\ClientNetworks\Validation;

use App\Events\Event;

class ValidationUpdateClientPaymentMethodEvent extends Event
{
    /**
     * @var array
     */
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}