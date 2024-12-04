<?php
namespace App\Http\Lib;

class SoapClientCurl extends \SoapClient{
 
    //Required variables
    public $url         = null;
    public $username    = null;
    public $password    = null;
    //public $passphrase  = null;
 
    //Overwrite constructor and add our variables
    public function __construct($wsdl, $options = array()){
 
        parent::__construct($wsdl, $options);
 
        foreach($options as $field => $value){
            if(!isset($this->$field)){
                $this->$field = $value;
            }
        }
    }
 
    /*
     * Overwrite __doRequest and replace with cURL. Return XML body to be parsed with SoapClient
     */
    public function __doRequest ($request, $location, $action, $version, $one_way = 0) {
 
        //Basic curl setup for SOAP call
       
      
    	 $headers = array(
                        "Content-type: text/xml;charset=\"utf-8\"",
                        "Accept: text/xml",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                        "SOAPAction: $action", 
                        "Content-length: ".strlen($request),
                    ); //SOAPAction: your op URL

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url); //Load from datasource
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 
        //SSL
        curl_setopt($ch, CURLOPT_SSLVERSION, 3); //=3
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response            = curl_exec ($ch);
        $this->curl_errorno  = curl_errno($ch);
        if ($this->curl_errorno == CURLE_OK) {
            $this->curl_statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        $this->curl_errormsg  = curl_error($ch);
        curl_close($ch);
        if($response){
            $texto=explode("<?xml version='1.0' encoding='utf-8'?>",$response);
            $stringxml=$texto[1];
            $xml=trim("<?xml version='1.0' encoding='utf-8'?>".$stringxml);   

            $doc = new \DOMDocument();
            $doc->loadXML($xml);
            return $doc->saveXML();

        }else{
            return "";
        }
        
    }
}

?>