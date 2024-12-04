<?php

namespace App\Events\Econtrol\Process;

class ProcessWsListSearchEvent
{
    /**
     * @var int
     */
    public $clientId;
    /**
     * @var int
     */
    public $listId;
    /**
     * @var string
     */
    public $filter;

    public function __construct(int $clientId, int $listId, string $filter)
    {
        $this->clientId = $clientId;
        $this->listId = $listId;
        $this->filter = $filter;
    }
}