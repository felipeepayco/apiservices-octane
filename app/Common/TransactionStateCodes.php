<?php

namespace App\Common;

final class TransactionStateCodes
{

    const ACCEPTED = 1;
    const REJECTED = 2;
    const PENDING = 3;
    const FAILED = 4;
    const REVERSED = 6;
    const HELD = 7;
    const INITIATED = 8;
    const EXPIRED = 9;
    const ABANDONED = 10;
    const CANCELLED = 11;
    const ANTI_FRAUD = 12;

    private function __construct()
    {
    }
}
