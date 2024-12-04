<?php
namespace App\Events\Customer\Validation;
use App\Events\Event;
/**
 *
 * @author Felipe
 * 
 */
class ValidationCustomerNewEvent extends Event{
    public $arr_parametros;
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}