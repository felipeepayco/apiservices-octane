<?php
namespace App\Service\V2\Purchase\Process;

use App\Repositories\V2\BblPurchaseRepository;

class UpdatePurchaseService
{
    private $bblPurchaseRepository;

    public function __construct(bblPurchaseRepository $bblPurchaseRepository)
    {
        $this->bblPurchaseRepository = $bblPurchaseRepository;
    }
    public function process($fieldValidation,$criteria)
    {

        $data = $this->bblPurchaseRepository->update($fieldValidation, $criteria);

        $success = true;
        $msg = 'Compra editada exitosamente';

        return [
            'success' => $success,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}
