<?php
namespace App\Service\V2\Note\Process;

use App\Repositories\V2\BblNoteRepository;

class DeleteNoteService
{
    private $bblNoteRepository;

    public function __construct(BblNoteRepository $bblNoteRepository)
    {
        $this->bblNoteRepository = $bblNoteRepository;
    }
    public function process($fieldValidation)
    {
        $data = $this->bblNoteRepository->destroy($fieldValidation["id"]);
        $success = true;
        $msg = 'Nota eliminada exitosamente';

        return [
            'success' => $success,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}
