<?php

namespace App\Exceptions;

class GeneralException extends \Exception{
    private $data;

    public function __construct($message,$data=[]){

        parent::__construct($message);
        $this->data = $data;
    }

    public function getData(){
        $arr_respuesta['totalerrors'] = 1;
        $arr_respuesta['errors'] = $this->data;
        return $arr_respuesta;
    }

}