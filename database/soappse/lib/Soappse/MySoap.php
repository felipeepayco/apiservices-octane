<?php

require_once __DIR__.'/src/soappse.php';
use \Exception;
use \ErrorException;
class Soappse_MySoap extends Soappse {   
   
    function __doRequest($request, $location, $saction, $version,$one_way = 0) {
        return parent::__doRequest($request, $location, $saction, $version,$one_way = 0);        
    }
    
}

?>
