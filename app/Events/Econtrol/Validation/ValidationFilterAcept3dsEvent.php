<?php

namespace App\Events\Econtrol\Validation;

class ValidationFilterAcept3dsEvent
{
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}
