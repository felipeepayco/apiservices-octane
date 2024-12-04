<?php

namespace App\Events\ClientNetworks\Validation;

use App\Events\Event;

class ValidationCreateOrUpdateClientNetworkEvent extends Event
{
    /**
     * @var array
     */
    public $parameters;

    /**
     * ValidationCreateClientNetwork constructor.
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}