<?php

namespace App\Events\Catalogue\Process\DiscountCode;

class CatalogueApplyDiscountCodeEvent
{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
