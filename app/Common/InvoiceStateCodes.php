<?php

namespace App\Common;

final class InvoiceStateCodes
{

    const UNPAID = 1;
    const PAID = 2;
    const CANCELED = 3;


    private function __construct()
    {
    }
}
