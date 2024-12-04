<?php namespace App\Helpers\Respuesta;

class Payer{
    private $names;
    private $lastnames;
    private $typeidentification;
    private $numberidentification;
    private $email;   
    private $phone;
    private $address;
    private $country;
    private $state;
    private $city;
    private $zipcode;
    private $ip;
    
    public function Payer(){
      $this->names="";
      $this->lastnames="";
      $this->typeidentification="";
      $this->numberidentification="";
      $this->email="";
      $this->address="";
      $this->phone="0000000";
      $this->country="";
      $this->state="";
      $this->city="";
      $this->zipcode="00000";
      $this->ip="";
    }
    
    public function setNames($names) {
        $this->names = $names;
    }

    public function setLastnames($lastnames) {
        $this->lastnames = $lastnames;
    }

    public function setTypeidentification($typeidentification) {
        $this->typeidentification = $typeidentification;
    }

    public function setNumberidentification($numberidentification) {
        $this->numberidentification = $numberidentification;
    }

    public function setEmail($email) {
        $this->email = $email;
    }  

    public function setAddress($address) {
        $this->address = $address;
    }

    public function setPhone($phone) {
        $this->phone = $phone;
    }

    public function setCountry($country) {
        $this->country = $country;
    }

    public function setState($state) {
        $this->state = $state;
    }

    public function setCity($city) {
        $this->city = $city;
    }

    public function setZipcode($zipcode) {
        $this->zipcode = $zipcode;
    }

    public function setIp($ip) {
        $this->ip = $ip;
    }   
}