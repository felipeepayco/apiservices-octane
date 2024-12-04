<?php

namespace App\Events\Econtrol\Process;

class ProcessDetailClientTransactionNoteUpdateEvent
{
    /**
     * @var int
     */
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}