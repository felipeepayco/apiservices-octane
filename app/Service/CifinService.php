<?php
namespace App\Service;

use App\EntityCifin\RespuestaCuestionarioBPDTO;
use App\EntityCifin\RespuestaPreguntaBPDTO;

class CifinService {

    private $wsdl;
    private $p_idcustomer;
    private $p_key;
    public $error_code;
    public $error_message;
    public $result;
    public $cuestionario;
    public $respuestacuestionario;

    public function __construct($p_idcustomer, $p_key,$p_tipo) {
       $this->wsdl='https://ff27543ecdd43d6f5d87-930274b85773a84f3712ef6d0abc5ad0.ssl.cf5.rackcdn.com/inspector.xml';
       $this->p_idcustomer=$p_idcustomer;
       $this->p_key= $p_key;
       $this->p_tipo=$p_tipo;
    }

    public function validarCedula($p_document,$p_fechaexpedicion,$p_test){

        $sclient   = new \SoapClient($this->wsdl, array('cache_wsdl' => WSDL_CACHE_NONE));
        $result=false;
        try {
            $result = $sclient->ValidarCedula($this->p_idcustomer,$this->p_key, $p_document, $p_fechaexpedicion, $this->p_tipo);
            if ($result->result == 1) {
                $this->result=$result;
                return $result;
            } else {
                $result=false;
            }
        } catch (SoapFault $fault) {
            $this->error_code=$fault->getCode();
            $this->error_message=$fault->getMessage();
            $this->result=false;
            $result=false;
        }
        return $result;
    }

    public function validarTarjeta($p_type_document, $p_document,$p_tarjeta,$p_test = false){

        $sclient = new \SoapClient($this->wsdl, array('cache_wsdl' => WSDL_CACHE_NONE));

        try {
            $this->result = $sclient->ValidarTarjeta($this->p_idcustomer, $this->p_key,$p_type_document , $p_document, $p_tarjeta, $p_test, $this->p_tipo);
        } catch (\SoapFault $fault) {
            $this->error_code = $fault->getCode();
            $this->error_message = $fault->getMessage();
            $this->result = false;
        }
        
       return $this->result;
    }

    public function getConfronta($p_type_document,$p_document,$p_test) {

        $sclient   = new \SoapClient($this->wsdl, array('cache_wsdl' => WSDL_CACHE_NONE));
        $preguntas = array();
        $result    = false;
        //$p_document="21400332";
        //$p_test="false";


        try {
            $result = $sclient->Confronta($this->p_idcustomer, $this->p_key, $p_type_document, $p_document, $p_test, $this->p_tipo);

            if ($result->respuestaProceso->codigoRespuesta == 1) {

                $this->result = true;
                $preguntas = $result->listadoPreguntas;
            } else {

                $this->result = false;
            }

            $this->cuestionario = $result;

        } catch (\SoapFault $fault) {

            $this->error_code=$fault->getCode();
            $this->error_message=$fault->getMessage();
            $this->result = false;

        }

        return json_encode(array('result'=>$result,'success'=>$this->result));
    }

    public function evaluarconfronta($codigocuestionario, $secuenciacuestionario, $preguntas, $p_test) {


        $sclient   = new \SoapClient($this->wsdl, array('cache_wsdl' => WSDL_CACHE_NONE));
        $p_respuestacuestionario = new RespuestaCuestionarioBPDTO();
        $p_respuestacuestionario->setCodigoCuestionario($codigocuestionario);
        $p_respuestacuestionario->setSecuenciaCuestionario($secuenciacuestionario);


        //se carga el detalle del comercio

        foreach ($preguntas as $key => $value) {
            $respuestapregunta = new RespuestaPreguntaBPDTO();
            $respuestapregunta->setSecuenciaPregunta($key);
            $respuestapregunta->setSecuenciaRespuesta($value);
            $p_respuestacuestionario->setRespuestapregunta($respuestapregunta);
        }
        
        try {
            $result = $sclient->evaluarConfronta($this->p_idcustomer, $this->p_key, $p_respuestacuestionario, $p_test);
            $this->result=true;
            $this->respuestacuestionario=$result;
        } catch (\SoapFault $fault) {
            $this->error_code=$fault->getCode();
            $this->error_message=$fault->getMessage();
            $result = false;
            $this->result=false;
        }
        return $result;
    }

}
