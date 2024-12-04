<?php
namespace App\Service\V2\Purchase\Process;

use App\Repositories\V2\BblPurchaseRepository;

class CreatePurchaseService
{
    private $bblPurchaseRepository;

    public function __construct(bblPurchaseRepository $bblPurchaseRepository)
    {
        $this->bblPurchaseRepository = $bblPurchaseRepository;
    }
    public function process($fieldValidation)
    {

        $data = $this->bblPurchaseRepository->create($fieldValidation);

        $success = true;
        $msg = 'Compra creada exitosamente';

        return [
            'success' => $success,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}
