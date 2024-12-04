<?php namespace App\Helpers\Respuesta;

class Card{
    
    private $franchise;
    private $number;
    private $expirymonth;
    private $expiryyear;
    private $securitycode;
    private $accounttypeid;
    private $financialid;
   
    public function getAccounttypeid() {
        return $this->accounttypeid;
    }
    public function Card(){
        $this->accounttypeid='1';
        $this->financialid='1';
    }
    public function setAccounttypeid($accounttypeid) {
        $this->accounttypeid = $accounttypeid;
    }

    public function getFinancialid() {
        return $this->financialid;
    }

    public function setFinancialid($financialid) {
        $this->financialid = $financialid;
    }

     public function setFranchise($franchise) {
        $this->franchise = $franchise;
    }
    public function setNumber($number) {
        $this->number = $number;
    }

    public function setExpirymonth($expirymonth) {
        $this->expirymonth = $expirymonth;
    }

    public function setExpiryyear($expiryyear) {
        $this->expiryyear = $expiryyear;
    }

    public function setSecuritycode($securitycode) {
        $this->securitycode = $securitycode;
    }   
}