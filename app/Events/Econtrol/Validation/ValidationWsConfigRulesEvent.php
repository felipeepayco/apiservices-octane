<?php

namespace App\Events\Econtrol\Validation;

class ValidationWsConfigRulesEvent
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