<?php


namespace App\Events\Catalogue\Process\DiscountCode;


class CatalogueDiscountCodeEvent
{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
