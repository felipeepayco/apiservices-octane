<?php 

use \Exception;
require_once __DIR__.'/soap-wsse.php';

class Soappse extends SoapClient{
    
    private $clavecertificado;
    private $certificado_key;
    private $certificado;
    //private $certificadoencript;
    
    function __doRequest($request, $location, $saction, $version,$one_way = 0) {

       
        $this->clavecertificado = 'Payco123456';
        $this->certificado_key = __DIR__ . '/Certificados/CertificadoKey.pem';
        $this->certificado = __DIR__ . '/Certificados/Certificado.pem';
        $this->certificadoencript = __DIR__ . '/Certificados/CertificadoEncript.pem';
        
        $namespace='http://www.uc-council.org/smp/schemas/eanucc';
        
        //$request = preg_replace ( '/<ns1:(\w+)/', '<$1 xmlns="' . $namespace . '"', $request, 1 );
        //$request = preg_replace ( '/<ns1:(\w+)/', '<$1', $request );
        //$request = str_replace ( array ('/ns1:', 'xmlns:ns1="' . $namespace . '"' ), array ('/', '' ), $request );
       


        $doc = new DOMDocument('1.0');  
        $doc->loadXML($request);  

        foreach ($doc->documentElement->childNodes AS $node) { 
            if ($node->localName == 'Body') { 
                $node->setAttribute("xmlns","http://www.uc-council.org/smp/schemas/eanucc");
                $node->firstChild->setAttribute('xmlns',"http://www.uc-council.org/smp/schemas/eanucc");
        
                break; 
            } 
        } 
        $request=$doc->saveXML();
       
        $doc = new DOMDocument('1.0');  
       
        $doc->loadXML($request);  
        $objWSSE = new WSSESoap($doc,false,false); 


        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));    
        $objKey->passphrase=$this->clavecertificado; 
        $objKey->loadKey($this->certificado_key, TRUE);   
        
        $options = array("insertBefore" => FALSE,'force_uri' => true);
        
        $objWSSE->signSoapDoc($objKey,$options);         
        
        $cert=file_get_contents($this->certificado);
        $token = $objWSSE->addBinaryToken($cert);             
        $objWSSE->attachTokentoSig($token);     
        $objWSSE->addTimestamp(3600);      
        $objKey = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
        $objKey->generateSessionKey(); 
        $this->soap_sent=$objWSSE->saveXML(); 
        $ret =  parent::__doRequest($this->soap_sent, $location, $saction, $version,$one_way = 0); 
        
        $this->__last_request = $this->soap_sent;
        return $ret;
                                  
        
    }  
    
    
 }
?>