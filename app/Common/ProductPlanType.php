<?php


namespace App\Common;


class ProductPlanType
{

    const PLAN_RECURRENTE = 1;
    const PLAN_PREPAGADO = 2;
    const PLAN_PORCENTUAL = 3;
    const PLAN_AGREGADOR = 4;
    const PLAN_FIJO = 5;
    const RECAUDO = 6;
    const SUSCRIPCIÓN = 7;
    const PAYPAL = 8;
    const SPLIT_PAYMENT = 9;
    const VENDE = 12;
    const MANAGE = 13;

    private function __construct()
    {
    }

}
