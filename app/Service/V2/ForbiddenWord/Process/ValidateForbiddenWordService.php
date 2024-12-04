<?php
namespace App\Service\V2\ForbiddenWord\Process;

use App\Repositories\V2\ForbiddenWordRepository;
use Illuminate\Http\Request;

class ValidateForbiddenWordService
{

    private $forbidden_words_repository;

    public function __construct(Request $request,
        ForbiddenWordRepository $forbidden_words_repository
    ) {
        $this->forbidden_words_repository = $forbidden_words_repository;

    }

    public function handle($params, $actions)
    {

        $forbidden_words = $this->forbidden_words_repository->get();
        $has_errors = false;
        $fw = "";

        foreach ($params as $p => $param) {
            foreach ($forbidden_words as $word) {

                if (str_contains(strtolower($param), strtolower($word->nombre))) {

                    $fw = [$p => $param];
                    $has_errors = true;
                    break 2;
                }
            }
        }
        if ($has_errors) {
            $last_action = $actions["endpoint_action"] . "_" . $actions["action"];
            $title_response = 'Error ' . $actions["endpoint_action"] . "_" . $actions["action"];
            $end_response_string =  (trim($actions["action"]) == "Tienda" || trim($actions["action"]) == "Categoría") ? "permitida" : "permitido";
            $text_response = $actions["action"] . " a " . $actions["endpoint_action"] . " no es " .$end_response_string;

            $data = [
                'totalErrors' => 1,
                'errors' => [
                    [
                        'codError' => 'AED100',
                        'errorMessage' => $text_response,
                    ],
                ],
            ];
            $response = [
                'success' => false,
                'titleResponse' => $title_response,
                'textResponse' => $text_response,
                'lastAction' => $last_action,
                'data' => $data,
                'word' => $fw,
            ];
            return $response;
        }

        return $response = [
            'success' => true,
            'titleResponse' => 'Valid word',
            'textResponse' => 'Valid word',
            'lastAction' => $actions["endpoint_action"],
            'data' => [],
        ];

    }

}
