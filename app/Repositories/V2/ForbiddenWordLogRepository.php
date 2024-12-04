<?php

namespace App\Repositories\V2;

use App\Models\V2\ForbiddenWordLog;

class ForbiddenWordLogRepository
{
    protected $forbiddenWordLog;

    public function __construct(ForbiddenWordLog $forbiddenWordLog)
    {
        $this->forbiddenWordLog = $forbiddenWordLog;
    }

    public function create($data)
    {

        $timeArray = explode(" ", microtime());
        $timeArray[0] = str_replace('.', '', $timeArray[0]);

        $data["id"] = (int) ($timeArray[1] . substr($timeArray[0], 2, 3));

        $newData = ["id" => $data["id"]];

        foreach ($data as $key => $value) {
            if ($key !== "id") {
                $newData[$key] = $value;
            }
        }

        return $data = $this->forbiddenWordLog->create($newData);

    }

    public function get()
    {

        return $data = $this->forbiddenWordLog->get();

    }

    public function find($id)
    {

        return $data = $this->forbiddenWordLog->find($id);

    }

    public function destroy($id)
    {

        return $data = $this->forbiddenWordLog->destroy($id);

    }

}
