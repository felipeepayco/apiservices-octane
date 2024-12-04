<?php

namespace App\Events\Econtrol\Process;

class ProcessConsultTransactionsRegistersEvent
{
    /**
     * @var int
     */
    public $clientId;
    /**
     * @var array
     */
    public $filters;
    /**
     * @var array
     */
    public $pagination;

    public function __construct(int $clientId, array $filters, array $pagination)
    {
        $this->clientId = $clientId;
        $this->filters = $filters;
        $this->pagination = $pagination;
    }
}