<?php

namespace App\Service\V2\MongoLog\Process;

use App\Repositories\V2\ForbiddenWordLogRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MongoLogService
{

    private $log;
    public function __construct(ForbiddenWordLogRepository $log)
    {
        $this->log = $log;
    }

    public function handle($params)
    {
        try
        {

            $this->log->create(
                ["modulo" => $params["module"],
                    "accion" => $params["action"],
                    "cliente_id" => $params["client_id"],
                    "palabra" => $params["word"],
                    "created_at" => Carbon::now(),
                    "updated_at" => null,
                ]);
        } catch (\Exception $e) {
            Log::info($e);
        }

    }
}
