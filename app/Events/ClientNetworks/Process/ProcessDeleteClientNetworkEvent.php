<?php

namespace App\Events\ClientNetworks\Process;

use App\Events\Event;

class ProcessDeleteClientNetworkEvent extends Event
{
    /**
     * @var array
     */
    public $parameters;

    /**
     * ProcessDeleteClientNetworkListener constructor.
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}
