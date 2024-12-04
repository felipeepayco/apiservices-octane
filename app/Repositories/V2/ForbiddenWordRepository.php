<?php

namespace App\Repositories\V2;

use App\Models\V2\ForbiddenWord;

class ForbiddenWordRepository
{
    protected $forbiddenWord;

    public function __construct(ForbiddenWord $forbiddenWord)
    {
        $this->forbiddenWord = $forbiddenWord;
    }

    public function get()
    {

        return $data = $this->forbiddenWord->get();

    }

    public function find($id)
    {

        return $data = $this->forbiddenWord->find($id);

    }

    public function destroy($id)
    {

        return $data = $this->forbiddenWord->destroy($id);

    }

}
