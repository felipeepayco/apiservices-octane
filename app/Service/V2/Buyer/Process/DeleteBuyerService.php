<?php
namespace App\Service\V2\Buyer\Process;
use App\Models\V2\BblBuyer;
use App\Repositories\V2\BblBuyerRepository;

class DeleteBuyerService{
    private $bblBuyerRepository;

    public function __construct(BblBuyerRepository $bblBuyerRepository)
    {
        $this->bblBuyerRepository = $bblBuyerRepository;
    }
    public function process($fieldValidation){
        $data       = $this->bblBuyerRepository->destroy($fieldValidation["buyerId"]);
        $success    = true;
        $msg        = 'Cliente eliminado exitosamente';

        return [
            'success'   => $success,
            'msg'       => $msg,
            'data'      => $data
        ];
    }
}