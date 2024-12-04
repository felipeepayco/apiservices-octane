<?php

namespace App\Common;

final class TaxCodes
{
    //valor porcentual de cada una de las retenciones utilizadas en facturacion electronica
    const IVA = 19;
    const RETE_IVA = 15;
    const RETE_FUENTE_25 = 2.5;
    const RETE_FUENTE_35 = 3.5;
    const RETE_ICA = 0.2;

    const RETEFUENTE_25_CODE = "RET25";
    const RETEFUENTE_35_CODE = "RET35";
    const RETEIVA_CODE = "ReteIVA";

    private function __construct()
    {
    }
}
