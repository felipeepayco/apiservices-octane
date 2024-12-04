<?php namespace App\Helpers\Respuesta;

/**
 * Banco
 */
class Banco {

    private $code;
    private $nombre;
    
    public function Banco(){
        $this->code="00";
        $this->nombre='Pruebas';
    }
    
    public function setCode($code) {
        $this->code = $code;
    }

    public function getCode() {
        return $this->code;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getNombre() {
        return $this->nombre;
    }
}
