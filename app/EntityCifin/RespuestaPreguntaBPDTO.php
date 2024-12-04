<?php

namespace App\EntityCifin;

class RespuestaPreguntaBPDTO{

 private $secuenciaPregunta;
 private $secuenciaRespuesta;

 public function setSecuenciaPregunta($secuenciaPregunta) {
     $this->secuenciaPregunta = $secuenciaPregunta;
     return $this;
 }
 public function getSecuenciaPregunta() {
     return $this->secuenciaPregunta;
 }

 public function setSecuenciaRespuesta($secuenciaRespuesta) {
     $this->secuenciaRespuesta = $secuenciaRespuesta;
     return $this;
 }
 public function getSecuenciaRespuesta() {
     return $this->secuenciaRespuesta;
 }



}

?>
