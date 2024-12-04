<?php

namespace App\Events\Services;

use App\Events\Event;

class ServicesSmsEvent extends Event
{
    public $params;

    /**
     * Undocumented function
     *
     * @param Request $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }
}
