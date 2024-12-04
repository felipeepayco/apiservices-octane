<?php

namespace App\Service\V2\Buyer\Validations;

use App\Repositories\V2\BblBuyerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeleteBuyerValidation
{
    private $bblBuyerRepository;
    public array $response;

    public function __construct(BblBuyerRepository $bblBuyerRepository)
    {
        $this->bblBuyerRepository = $bblBuyerRepository;
    }

    public function validation(Request $request)
    {
        $request->merge([
            'bblClientId' => $request->input('clientId'),
            'buyerId' => $request->input('buyerId'),
        ]);
        $validator = Validator::make($request->all(), [
            'buyerId' => 'required|integer|gt:0|digits_between:1,10',
            'bblClientId' => 'required|numeric',
        ], [
            'buyerId.required' => 'El id del cliente es obligatorio.',
            'buyerId.integer' => 'El id del cliente debe ser un entero',
            'buyerId.gt' => 'El id del cliente debe ser mayor a 0',
            'buyerId.digits_between' => 'El id del cliente debe tener entre 1 y 10 digitos'
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
        $this->response = $request->all();

        return true;
    }
}
