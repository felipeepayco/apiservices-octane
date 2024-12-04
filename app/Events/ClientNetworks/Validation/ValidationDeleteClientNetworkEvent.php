<?php

namespace App\Events\ClientNetworks\Validation;

use App\Events\Event;

class ValidationDeleteClientNetworkEvent extends Event
{
    /**
     * @var array
     */
    public $parameters;

    /**
     * ValidationDeleteClientNetworkListener constructor.
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}
