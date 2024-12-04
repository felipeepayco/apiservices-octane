<?php

namespace App\Service\V2\Note\Validations;

use App\Repositories\V2\BblBuyerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreateNoteValidation
{
    public array $response;
    private $bblBuyerRepository;

    public function __construct(BblBuyerRepository $bblBuyerRepository)
    {
        $this->bblBuyerRepository = $bblBuyerRepository;

    }

    public function validation(Request $request)
    {
        $request->merge([
            'nota' => $request->input('note'),
            'bbl_comprador_id' => $request->input('buyerId'),
        ]);

        $validator = Validator::make($request->all(), [
            'nota' => 'required|max:200',
            'buyerId' => 'required|integer|gt:0|digits_between:1,10',
        ], [
            'nota.max' => 'note field can not be greater than :max characters',
            'nota.required' => 'note field is required.',
            'buyerId.required' => 'buyerId field is required.',
            'buyerId.integer' => 'buyerId field must be an integer',
            'buyerId.gt' => 'buyerId field must be positive and greater than 0',
            'buyerId.digits_between' => 'buyerId field must be within a range of 1 and 10 digits',

        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'titleResponse' => 'error',
                'textResponse' => $validator->errors()->all(),
                'lastAction' => 'validation',
                'data' => null,
            ];
            $this->response = $response;
            return false;
        }

        if (empty($this->bblBuyerRepository->find($request->input('buyerId')))) {
            $response = [
                'success' => false,
                'titleResponse' => 'error',
                'textResponse' => "Comprador invalido",
                'lastAction' => 'validation',
                'data' => null,
            ];
            $this->response = $response;
            return false;
        };

        $this->response = $request->all();
        return true;
    }
}
