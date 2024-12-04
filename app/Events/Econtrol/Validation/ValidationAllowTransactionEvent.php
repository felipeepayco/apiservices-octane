<?php


namespace App\Events\Econtrol\Validation;

class ValidationAllowTransactionEvent
{
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}