<?php

namespace App\Events\MongoTransaction\Process;

use App\Events\Event;

/**
 * Description of ProcessCreateMongoTransactionEvent
 *
 * @author Cristian Gonzalez
 */
class ProcessCreateMongoTransactionEvent extends Event {
    public $arr_parametros;
    public function __construct($arr_parametros) {
        $this->arr_parametros = $arr_parametros;
    }
}