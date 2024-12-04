<?php

namespace App\Events\Econtrol\Process;

class ProcessUploadDocumentEvent
{
    /**
     * @var array
     */
    public $arr_parameters;

    public function __construct(array $arr_parameters)
    {
        $this->arr_parameters = $arr_parameters;
    }

}