<?php


namespace App\Events\Econtrol\Validation;

class ValidationDenyTransactionEvent
{
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}