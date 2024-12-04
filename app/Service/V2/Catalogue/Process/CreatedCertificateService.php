<?php

namespace App\Service\V2\Catalogue\Process;
use App\Helpers\Pago\HelperPago;
use App\Repositories\V2\CatalogueRepository;


class CreatedCertificateService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;

    public function __construct(CatalogueRepository $catalogueRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
    }
    public function process($data)
    {
        $id=$data['id'];
        $this->catalogueRepository->createCertificate($id);
    }
}