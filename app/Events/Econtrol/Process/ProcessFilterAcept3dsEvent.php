<?php

namespace App\Events\Econtrol\Process;

class ProcessFilterAcept3dsEvent
{
    /**
     * @var array
     */
    public $arr_parameters;

    public function __construct(int $clientId, int $filter_id)
    {
        $this->clientId = $clientId;
        $this->filter_id = $filter_id;
    }
}