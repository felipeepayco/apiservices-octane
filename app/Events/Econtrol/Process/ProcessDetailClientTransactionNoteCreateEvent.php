<?php

namespace App\Events\Econtrol\Process;

class ProcessDetailClientTransactionNoteCreateEvent
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