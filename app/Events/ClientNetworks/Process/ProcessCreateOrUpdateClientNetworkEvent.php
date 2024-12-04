<?php

namespace App\Events\ClientNetworks\Process;

use App\Events\Event;
use App\Models\PseConfigSitio;
use App\Models\TrSitio;

class ProcessCreateOrUpdateClientNetworkEvent extends Event
{
    /**
     * @var array
     */
    public $parameters;

    /**
     * @var TrSitio|PseConfigSitio
     */
    public $clientTrSitio;

    /**
     * ProcessCreateOrUpdateClientNetworkListener constructor.
     * @param array $parameters
     * @param TrSitio|null $clientTrSitio
     */
    public function __construct(array $parameters, $clientTrSitio = null)
    {
        $this->parameters = $parameters;
        $this->clientTrSitio = $clientTrSitio;
    }
}