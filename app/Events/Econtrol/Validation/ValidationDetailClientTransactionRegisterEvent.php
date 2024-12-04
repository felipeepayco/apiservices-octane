<?php


namespace App\Events\Econtrol\Validation;

class ValidationDetailClientTransactionRegisterEvent
{
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}