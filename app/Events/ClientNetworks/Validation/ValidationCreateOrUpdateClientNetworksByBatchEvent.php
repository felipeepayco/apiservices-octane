<?php

namespace App\Events\ClientNetworks\Validation;

use App\Events\Event;

class ValidationCreateOrUpdateClientNetworksByBatchEvent extends Event
{
    /**
     * @var array
     */
    public $parameters;

    /**
     * ValidationCreateOrUpdateClientNetworksByBatchListener constructor.
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}
