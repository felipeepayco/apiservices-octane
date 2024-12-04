<?php

namespace App\Events\ClientNetworks\Process;

use App\Events\Event;
use App\Models\MediosPagoClientes;

class ProcessUpdateClientPaymentMethodEvent extends Event
{
    /**
     * @var array
     */
    public $parameters;

    /**
     * ProcessUpdateClientPaymentMethodEvent constructor.
     * @param array $parameters
     * @param MediosPagoClientes $paymentMethod
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}