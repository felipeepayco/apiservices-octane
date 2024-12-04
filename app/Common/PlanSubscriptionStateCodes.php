<?php
namespace App\Common;

final class PlanSubscriptionStateCodes
{

    const ACTIVE=1;
    const PENDING =2;
    const CANCELED=3;
    const EXPIRED=4;
    const INTEGRATION =5;
    const POST_PRODUCTION=6;
    const INACTIVE=7;
    const SUSPENDED=8;
    const TRANSITION=9;
    const ACTIVE_PENDING=10;
    const REVOKED=11;

    private function __construct()
    {
    }
}
