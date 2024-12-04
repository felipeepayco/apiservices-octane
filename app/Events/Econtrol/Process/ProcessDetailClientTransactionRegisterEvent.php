<?php

namespace App\Events\Econtrol\Process;

class ProcessDetailClientTransactionRegisterEvent
{
    /**
     * @var int
     */
    public $clientId;
    /**
     * @var int
     */
    public $id;

    public function __construct(int $clientId, int $id)
    {
        $this->clientId = $clientId;
        $this->id = $id;
    }
}