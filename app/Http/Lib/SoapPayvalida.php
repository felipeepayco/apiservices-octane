<?php namespace App\Http\Lib;
/**
 *Cristian Cortés - Powered by ePayco
 */

class SoapPayvalida
{

  private $client;
  private $ns;

  private $fixed_hash;
  private $merchantID;
  private $password;
  private $emailCliente;
  private $codigoPais;
  private $ordenID;
  private $referentePago;
  private $moneda;
  private $monto;
  private $descripcion;
  private $tiempoDeVida;
  private $metodoPago;

  function __construct(){}

  public function createClient(){

  }

  public function getNs(){
    return $this->ns;
  }

  public function setNs($value){
    $this->ns = $value;
  }

  public function getMerchantID(){
    return $this->merchantID;
  }

  public function setMerchantID($value){
    $this->merchantID = $value;
  }

  public function getPassword(){
    return $this->password;
  }

  public function setPassword($value){
    $this->password = $value;
  }

  public function getEmailCliente(){
    return $this->emailCliente;
  }

  public function setEmailCliente($value){
    $this->emailCliente = $value;
  }

  public function getCodigoPais(){
    return $this->codigoPais;
  }

  public function setCodigoPais($value){
    $this->codigoPais = $value;
  }

  public function getOrdenId(){
    return $this->ordenID;
  }

  public function setOrdenId($value){
    $this->ordenID = $value;
  }

  public function getReferentePago(){
    return $this->referentePago;
  }

  public function setReferentePago($value){
    $this->referentePago = $value;
  }

  public function getMoneda(){
    return $this->moneda;
  }

  public function setMoneda($value){
    $this->moneda = $value;
  }

  public function getMonto(){
    return $this->monto;
  }

  public function setMonto($value){
    $this->monto = $value;
  }

  public function getDescripcion(){
    return $this->descripcion;
  }

  public function setDescripcion($value){
    $this->descripcion = $value;
  }

  public function getTiempoDeVida(){
    return $this->tiempoDeVida;
  }

  public function setTiempoDeVida($value){
    $this->tiempoDeVida = $value;
  }

  public function getMetodoPago(){
    return $this->metodoPago;
  }

  public function setMetodoPago($value){
    $this->metodoPago = $value;
  }

  public function getChecksum(){
    return $this->checksum;
  }

  public function setChecksum($value){
    $this->checksum = $value;
  }

  public function getFixedHash(){
    return $this->fixed_hash;
  }

  public function setFixedHash($value){
    $this->fixed_hash = $value;
  }

  public function getSoapFunctions(){
    return $this->client->__getFunctions();
  }

  public function getListaMediosPago(){
    return $this->client->getListaMediosPago();
  }

  private function cleanParameter($value){
    $valor1 = @end(explode(':', $value));
    $valor1 = substr($valor1, 1 , strlen($valor1)-2);
    return $valor1;
  }

  public function callOCUnica(){

    $datos = array(
      'merchantID' => $this->getMerchantID(),
      'password' => $this->getPassword(),
      'emailCliente' => $this->getEmailCliente(),
      'codigoPais' => $this->getCodigoPais(),
      'ordenID' => $this->getOrdenId(),
      'referentePago' => $this->getReferentePago(),
      'moneda' => $this->getMoneda(),
      'monto' => $this->getMonto(),
      'descripcion' => $this->getDescripcion(),
      'tiempoDeVida' => $this->getTiempoDeVida(),
      'metodoPago' => $this->getMetodoPago(),
      'checksum' => $this->getChecksum()
    );

    try {

      $this->client = new \SoapClient($this->getNs(), array('cache_wsdl' => WSDL_CACHE_NONE));

      $response = $this->client->setOCUnica($datos);
     
      $data = explode(';', $response->setOCUnicaReturn);

      $cod_error = $this->cleanParameter($data[0]);
      $mensaje = $data[1];

      $data_response=new \stdClass();
      $data_response->cod_error = $cod_error;

      if($cod_error == '0000'){
        $data_response->success=1;
        $data_response->OrdenID = $this->cleanParameter($data[1]);
        $data_response->Referencia = $this->cleanParameter($data[2]);
        $data_response->Monto = $this->cleanParameter($data[3]);
        $data_response->PVordenID = $this->cleanParameter($data[4]);

      } else {

        $data_response->success=0;
        $data_response->messagge=$mensaje;

      }
    } catch (\SoapFault $e) {

      $data_response=new \stdClass();
      $data_response->success=0;
      $data_response->cod_error='9999';
      $data_response->messagge='Error de comunicación con el centro de autorización.';

    }
    return $data_response;
  }
}
