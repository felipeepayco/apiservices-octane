<?php namespace App\Helpers\Respuesta;

class Purchase{
    
    private $reference;
    private $description;    
    private $currency;
    private $totalamount;
    private $tax;
    private $basetax;
    private $aditionaldata;    
    private $numberquotas;
    
    public function getReference() {
        return $this->reference;
    }

    public function setReference($reference) {
        $this->reference = $reference;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function setCurrency($currency) {
        $this->currency = $currency;
    }

    public function getTotalamount() {
        return $this->totalamount;
    }

    public function setTotalamount($totalamount) {
        $this->totalamount = $totalamount;
    }

    public function getTax() {
        return $this->tax;
    }

    public function setTax($tax) {
        $this->tax = $tax;
    }

    public function getBasetax() {
        return $this->basetax;
    }

    public function setBasetax($basetax) {
        $this->basetax = $basetax;
    }

    public function getAditionaldata() {
        return $this->aditionaldata;
    }

    public function setAditionaldata($aditionaldata) {
        $this->aditionaldata = $aditionaldata;
    }

    public function getNumberquotas() {
        return $this->numberquotas;
    }

    public function setNumberquotas($numberquotas) {
        $this->numberquotas = $numberquotas;
    }   
}