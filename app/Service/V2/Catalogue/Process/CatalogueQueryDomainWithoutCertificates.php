<?php

namespace App\Service\V2\Catalogue\Process;

use App\Helpers\Pago\HelperPago;
use App\Repositories\V2\CatalogueRepository;
use App\Helpers\Validation\CommonValidation;


class CatalogueQueryDomainWithoutCertificates extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;

    public function __construct(CatalogueRepository $catalogueRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
    }
    public function process($data)
    {
        $catalogue = $this->catalogueRepository->getCataloguesWithoutCertificates();
        if (!$catalogue->eliminado_valor_subdominio_propio) {
            $domain = $catalogue->valor_subdominio_propio . '.' . $catalogue->valor_dominio_propio;
        } else {
            $domain = $catalogue->valor_dominio_propio;
        }
        $this->catalogueRepository->nextAttempt($catalogue->id, CommonValidation::getFieldValidation($catalogue->toArray(), 'intentos_certificacion', 0));
        return $catalogue->id . "," . $domain;
    }
}