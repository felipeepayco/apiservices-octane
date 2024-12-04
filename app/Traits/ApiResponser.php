<?php

namespace App\Traits;


/**
 * Standard ApiResponse implementation
 */
trait ApiResponser
{
    protected function defaultApiResponse(bool $success, $title_response = '', $text_response = '', $last_action = '', $data = [], $code = 200)
    {
        return response()->json([
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        ], $code);
    
    }
}
