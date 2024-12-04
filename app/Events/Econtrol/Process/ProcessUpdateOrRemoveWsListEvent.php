<?php

namespace App\Events\Econtrol\Process;

class ProcessUpdateOrRemoveWsListEvent
{
    /**
     * @var int
     */
    public $clientId;
    /**
     * @var array
     */
    public $parameters;

    public function __construct(int $clientId, array $parameters)
    {
        $this->clientId = $clientId;
        $this->parameters = $parameters;
    }
}