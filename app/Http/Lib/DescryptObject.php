<?php namespace App\Http\Lib;
use App\Http\Lib\Aes;

/**
 * Description of DescryptObject
 *
 * @author programacion-james
 */
class DescryptObject {

    private $blockSize = '256';
    private $arr_set = array();
    private $arr_get = array();
    private $method = array();
    private $filterGet = 'get';
    private $filterSet = 'set';
    private $get = array();
    private $set = array();

    
    
    function setTextEncript($inputText, $key) {
        $aes = new Aes($inputText, $key, $this->blockSize);
        $enc = $aes->encrypt();
        return $enc;
    }

    function getTextDecript($inputText, $key) {
        $aes = new Aes(null, $key, $this->blockSize);
        $aes->setData($inputText);
        $value = $aes->decrypt();
        return $value;
    }

    function descrypt($object, $key) {
        // se obtienen los métedos de la clase       
               
        $this->arr_get=array();
        $this->arr_set=array();
        $this->set=array();
        $this->get=array();        
        $this->getMethods($object);
        $arr_get=$this->arr_get;
        $arr_set=$this->arr_set;
        
        
        for ($index = 0; $index < count($this->get); $index++) {
            $inputText = $object->$arr_get[$index]();
            $temp = "";
            if (is_array($inputText)) {
                $temp = array();
                foreach ($inputText as $key => $value) {
                    if ($value != "") {
                        $aes = new Aes(null, $key, $this->blockSize);
                        $aes->setData($value);
                        $decript = $aes->decrypt();
                    } else {
                        $decript = "";
                    }
                    $temp[$key] = $decript;
                }
            } else {
               $aes = new Aes(null, $key, $this->blockSize);
               $aes->setData($inputText);
               $temp = $aes->decrypt();
            }
            $object->$arr_set[$index]($temp);
        }
        return $object;     
    }

    function encrypt($object, $key) {
        // se obtienen los métedos de la clase
        $clone= clone ($object);
        $this->arr_get=array();
        $this->arr_set=array();
        $this->set=array();
        $this->get=array();        
        $this->getMethods($object);        
        $arr_get=$this->arr_get;
        $arr_set=$this->arr_set;
        
        for ($index = 0; $index < count($this->get); $index++) {
            $inputText =  $clone->$arr_get[$index]();
           
            $temp = "";
            if (is_array($inputText)) {
                $temp = array();
                foreach ($inputText as $key => $value) {
                    if ($value != "") {
                        $aes = new Aes($value, $key, $this->blockSize);
                        $enc = $aes->encrypt();
                    } else {
                        $enc = "";
                    }
                    $temp[$key] = $enc;
                }
            } else {
                $aes = new Aes($inputText, $key, $this->blockSize);
                $enc = $aes->encrypt();
                $temp = $enc;
            }
             $clone->$arr_set[$index]($temp);
        }
        return  $clone;
    }
    
    function cloneclass($object) {
        // se obtienen los métedos de la clase
        $this->arr_get=array();
        $this->arr_set=array();
        $this->set=array();
        $this->get=array();     
 
        $this->getMethods($object);        
        $arr_get=$this->arr_get;
        $arr_set=$this->arr_set;
        
        for ($index = 0; $index < count($this->get); $index++) {
            $inputText = $object->$arr_get[$index]();           
            $temp = "";
            if (is_array($inputText)) {
                $temp = array();
                foreach ($inputText as $key => $value) {
                     $temp[$key] = $value;
                }
            } else {
                
                $temp = $value;
            }
            $object->$arr_set[$index]($temp);
        }
        return $object;
    }

    private function getMethods($object) {
        $this->method = get_class_methods($object);
        $filterGet = $this->filterGet;
        $this->get = array_filter($this->method, function ($item) use ($filterGet) {
                    if (stripos($item, $filterGet) !== false) {
                        if (substr($item, 0, 3) == $filterGet) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                    return false;
                });
        $filterSet = $this->filterSet;
        $this->set = array_filter($this->method, function ($item) use ($filterSet) {
                    if (stripos($item, $filterSet) !== false) {
                        if (substr($item, 0, 3) == $filterSet) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                    return false;
                });

        foreach ($this->get as $value) {
            $this->arr_get[] = $value;
        }
        foreach ($this->set as $value) {
            $this->arr_set[] = $value;
        }
    }

}
