<?php


namespace App\Helpers\Respuesta;

class ProformaInvoice {
    
    public $data;
    public $type;
    public $generalException;
    static function response($data, $type, $generalException){
        
            $success = $type === "success" ? true : false;
            $title_response = $type === "success" ? 'Successful consult' : $generalException->getMessage();
            $text_response = $type === "success" ? 'successful consult' : $generalException->getMessage();
            $last_action = $type === "success" ? 'successful consult' : 'generalException';
            $data = $type === "success"  ? $data : $generalException->getData();

            $arr_respuesta['success'] = $success;
            $arr_respuesta['titleResponse'] = $title_response;
            $arr_respuesta['textResponse'] = $text_response;
            $arr_respuesta['lastAction'] = $last_action;
            $arr_respuesta['data'] = $data;
        
        return $arr_respuesta;
    }
}