<?php


namespace App\Events\Econtrol\Validation;

class ValidationVerificationTransactionEvent
{
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}