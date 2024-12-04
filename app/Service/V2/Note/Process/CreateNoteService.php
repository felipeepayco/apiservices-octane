<?php
namespace App\Service\V2\Note\Process;

use App\Repositories\V2\BblNoteRepository;

class CreateNoteService
{
    private $bblNoteRepository;

    public function __construct(bblNoteRepository $bblNoteRepository)
    {
        $this->bblNoteRepository = $bblNoteRepository;
    }
    public function process($fieldValidation)
    {

        $data = $this->bblNoteRepository->create($fieldValidation);

        $success = true;
        $msg = 'Nota creada exitosamente';

        return [
            'success' => $success,
            'msg' => $msg,
            'data' => [
                'note' => $data['nota'], 
                'buyerId' => $data['bbl_comprador_id'], 
                'updatedAt' => $data['updated_at'], 
                'createdAt' => $data['created_at'], 
                'id' => $data['id'], 
            ],
        ];
    }
}
