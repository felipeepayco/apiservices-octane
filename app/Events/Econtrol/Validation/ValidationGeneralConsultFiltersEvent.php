<?php

namespace App\Events\Econtrol\Validation;

class ValidationGeneralConsultFiltersEvent
{
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}