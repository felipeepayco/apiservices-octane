<?php

namespace App\Service\V2\Note\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeleteNoteValidation
{
    public array $response;

    public function __construct()
    {
    }

    public function validation(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|gt:0|digits_between:1,10',
        ], [
            'id.required' => 'id field is required.',
            'id.integer' => 'id field must be an integer',
            'id.gt' => 'id field must be positive and greater than 0',
            'id.digits_between' => 'id field must be within a range of 1 and 10 digits'

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
