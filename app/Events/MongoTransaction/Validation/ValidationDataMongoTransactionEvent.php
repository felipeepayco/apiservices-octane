<?php

namespace App\Events\MongoTransaction\Validation;

use App\Events\Event;

/**
 * Description of ValidationDataMongoTransactionEvent
 *
 * @author Cristian Gonzalez 
 */
class ValidationDataMongoTransactionEvent extends Event {
    public $arr_parametros;
    public function __construct($arr_parametros) {
        $this->arr_parametros = $arr_parametros;
    }
}