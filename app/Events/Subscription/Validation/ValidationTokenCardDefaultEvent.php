<?php
namespace App\Events\Subscription\Validation;
use App\Events\Event;
/**
 *
 * @author Leomar
 * 
 */
class ValidationTokenCardDefaultEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}