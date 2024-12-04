<?php

namespace App\Events\ClientNetworks\Process;

use App\Events\Event;

class ProcessCreateOrUpdateClientNetworksByBatchEvent extends Event
{
    /**
     * @var array
     */
    public $parameters;

    /**
     * ProcessCreateOrUpdateClientNetworksByBatchListener constructor.
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}
