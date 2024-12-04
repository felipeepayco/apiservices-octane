<?php namespace App\Helpers\Respuesta;

class Transaction{
    
    public $transactionId;
    public $commerceId;
    public $key;
    public $purchaseData;
    public $cardData;
    public $payerData;
    public $type_transaction;
    
    public function Transaction(){
        $this->transactionId='0000000';
    }    
    public function setCommerceId($commerceId) {
        $this->commerceId = $commerceId;
    }
    public function setTransactionId($transactionId) {
        $this->transactionId = $transactionId;
    }
    public function setKey($key) {
        $this->key = $key;
    }

    public function setPurchaseData(Purchase $purchaseData) {
        $this->purchaseData = $purchaseData;
    }

    public function setCardData(Card $cardData) {
        $this->cardData = $cardData;
    }

    public function setPayerData(Payer $payerData) {
        $this->payerData = $payerData;
    }
    public function getTypeTransaction() {
        return $this->type_transaction;
    }

    public function setTypeTransaction($type_transaction) {
        $this->type_transaction=$type_transaction;
    }
}