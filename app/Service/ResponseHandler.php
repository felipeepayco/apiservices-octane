<?php

namespace App\Service;

use App\Helpers\Messages\CommonText;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ResponseHandler
{
    /**
     * Return a response properly structured.
     * @param array $data
     * @param int $code
     * @return JsonResponse
     */
    public static function createJsonResponse(array $data, int $code = 200): JsonResponse
    {
        return response()->json($data, $code);
    }

    /**
     * Return the structure for a success response.
     * @param array $data
     * @param string $title
     * @param string $text
     * @param string $lastAction
     * @return array
     */
    public static function generateSuccessResponseDataStructure(array $data, string $title, string $text, string $lastAction): array
    {
        return [
            CommonText::SUCCESS => true,
            CommonText::TITLE_RESPONSE => $title,
            CommonText::TEXT_RESPONSE => $text,
            CommonText::LAST_ACTION => $lastAction,
            CommonText::DATA => $data
        ];
    }

    /**
     * Return the structure for a bad response.
     * @param array $data
     * @param string $title
     * @param string $text
     * @param string $lastAction
     * @param Validator|null $validator
     * @return array
     */
    public static function generateBadResponseDataStructure(array $data, string $title, string $text, string $lastAction, Validator $validator = null): array
    {
        $responseData = [
            CommonText::SUCCESS => false,
            CommonText::TITLE_RESPONSE => $title,
            CommonText::TEXT_RESPONSE => $text,
            CommonText::LAST_ACTION => $lastAction,
            CommonText::DATA => $data
        ];

        if (!is_null($validator)) {
            $responseData[CommonText::DATA]["totalErrors"] = count($validator->errors());
            $responseData[CommonText::DATA]["errors"] = $validator->errors();
        }

        return $responseData;
    }

    /**
     * Validate the status code of the response and throw the proper exception.
     * @param $response
     * @throws Throwable
     */
    public static function validateResponse($response)
    {
        if (empty($response)) {
            throw new Exception('No response from the server');
        }

        if (isset($response['header_code']) && $response['header_code'] === "500") {
            throw new Exception($response['body']->message, 500);
        }

        if (isset($response['header_code']) && $response['header_code'] === "400") {
            throw new BadRequestHttpException($response['body']->message, null, 400);
        }

        if (isset($response['header_code']) && $response['header_code'] === "404") {
            throw new NotFoundHttpException($response['body']->message, null, 404);
        }
    }
}
