<?php
namespace App\Service\V2\Subscription\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DisableNotificationValidation
{

    public function validation(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'subscriptionId' => 'required|numeric|digits_between:1,10|gt:0',
        ],
        [
         
            'subscriptionId.required' => 'subscriptionId field is required.',
            'subscriptionId.digits_between' => 'subscriptionId field can not exceen 10 digits',
            'subscriptionId.numeric' => 'subscriptionId field must be numeric',
            'subscriptionId.gt' => 'subscriptionId field must be greater than 0',


        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'titleResponse' => 'error',
                'textResponse' => $validator->errors()->all(),
                'lastAction' => 'validation',
                'data' => null,
            ];
            return $response;
        }

        return [
            'success' => true,
            'titleResponse' => 'Success',
            'textResponse' => 'Success',
            'lastAction' => 'validation',
            'data' => $request->all(),
        ];
    }

}
