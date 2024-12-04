<?php
namespace App\Service\V2\Buyer\Process;

use App\Repositories\V2\BblBuyerRepository;

class UpdateBuyerService
{
    private $bblBuyerRepository;

    public function __construct(BblBuyerRepository $bblBuyerRepository)
    {
        $this->bblBuyerRepository = $bblBuyerRepository;
    }
    public function process($fieldValidation)
    {

        $criteria = ["id" => $fieldValidation["comprador_id"]];

        $data = $this->bblBuyerRepository->update($fieldValidation, $criteria);
        $success = true;
        $msg = 'Cliente modificado exitosamente';

        return [
            'success' => $success,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}
