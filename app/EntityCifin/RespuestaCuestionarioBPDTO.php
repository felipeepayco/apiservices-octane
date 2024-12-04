<?php

//Respuesta por parte  del cliente dada al cuestionario para enviar a cifin con el metodo evaluarCuestionario
namespace App\EntityCifin;

class RespuestaCuestionarioBPDTO {

   private $codigoCuestionario;
   private $secuenciaCuestionario;
   private $respuestasCuestionario;

   public function RespuestaCuestionarioBPDTO(){
       $this->respuestasCuestionario=array();
   }

   public function getCodigoCuestionario() {
       return $this->codigoCuestionario;
   }

   public function setCodigoCuestionario($codigoCuestionario) {
       $this->codigoCuestionario = $codigoCuestionario;
       return $this;
   }

   public function getSecuenciaCuestionario() {
       return $this->secuenciaCuestionario;
   }

   public function setSecuenciaCuestionario($secuenciaCuestionario) {
       $this->secuenciaCuestionario = $secuenciaCuestionario;
   }

   public function setRespuestapregunta(RespuestaPreguntaBPDTO $respuestapregunta) {
       $this->respuestasCuestionario[] = $respuestapregunta;
   }
   public function getRespuestasCuestionario() {
       return $this->respuestasCuestionario;
   }



}
?>
