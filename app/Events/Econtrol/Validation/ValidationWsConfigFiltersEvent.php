<?php

namespace App\Events\Econtrol\Validation;

class ValidationWsConfigFiltersEvent
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