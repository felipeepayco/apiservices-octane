<?php

namespace App\Events\Econtrol\Process;

class ProcessCloneEcontrolRulesEvent
{
    /**
     * @var int
     */
    public $clientId;

    public function __construct(int $clientId)
    {
        $this->clientId = $clientId;
    }

}