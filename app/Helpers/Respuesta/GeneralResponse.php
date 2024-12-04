<?php

namespace App\Helpers\Respuesta;

class GeneralResponse {
    
    public $data;
    public $type;
    public $message;
    public $action;

    static function response($success, $message, $action, $data){
        $title_response = $success ? 'Successful consult' : 'Error';

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $message;
        $arr_respuesta['lastAction'] = $action;
        $arr_respuesta['data'] = $data;
        
        return $arr_respuesta;
    }
}