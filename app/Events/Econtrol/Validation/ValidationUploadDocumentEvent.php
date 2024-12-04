<?php

namespace App\Events\Econtrol\Validation;

class ValidationUploadDocumentEvent
{
    public $arr_parameters;
    
    public function __construct($arr_parameters)
    {
        $this->arr_parameters = $arr_parameters;
    }
}