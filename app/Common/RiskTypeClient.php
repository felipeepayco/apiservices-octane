<?php

namespace App\Common;

final class RiskTypeClient
{
    const WITH_EXCEPTION = 1; // esta en listas pero se le permite utilizar los servicios
    const OBJECTIVE = 2; // el cliente no esta en listas
    const NOT_OBJECTIVE = 3; // cliente en listas
}
