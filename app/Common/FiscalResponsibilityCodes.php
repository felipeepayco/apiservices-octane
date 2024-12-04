<?php

namespace App\Common;

final class FiscalResponsibilityCodes
{
    // id de cada una de las responsabilidades fiscales utilizadas en facturacion electronica
    const SIMPLIFIED = 110;
    const NOT_RESPONSIBLE = 115;
    const NOT_IVA_RESPONSIBLE_PJ = 112;
    const IVA_RESPONSIBLE = 111;
    const BIG_CONTRIBUTOR = 5;
    const SELFRETAINER = 7;
    const ICA_RESPONSIBLE = 116;
    const RET_IVA_RESPONSIBLE = 12;

    private function __construct()
    {
    }
}
