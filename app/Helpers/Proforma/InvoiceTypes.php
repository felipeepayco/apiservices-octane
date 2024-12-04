<?php


namespace App\Helpers\Proforma;


final class InvoiceTypes
{
    const AGGREGATOR_TRANSACTION = 1;
    const AGGREGATOR_WITHDRAWAL = 2;
    const CUSTOM = 3;
    const GATEWAY_PACKAGE = 4;
    const GATEWAY_AFFILIATION = 5;
    const AGGREGATOR_CONSOLIDATED = 6;
    const COLLECTION = 7;
    const PAYMENT = 8;
    const SOCIAL_SELLER = 9;


    private function __construct()
    {
    }
}