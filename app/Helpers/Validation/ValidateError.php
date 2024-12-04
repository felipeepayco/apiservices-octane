<?php

namespace App\Helpers\Validation;

class ValidateError
{
  public static function validateError($validate) {
    $success         = false;
    $last_action     = 'validation clientId y data of filter';
    $title_response  = 'Error';
    $text_response   = 'Some fields are required, please correct the errors and try again';

    $data            =
        array('totalerrors'=>$validate->totalerrors,
            'errors'=>$validate->errorMessage);
    $response=array(
        'success'         => $success,
        'titleResponse'   => $title_response,
        'textResponse'    => $text_response,
        'lastAction'      => $last_action,
        'data'            => $data
    );
    return $response;
  }
}
