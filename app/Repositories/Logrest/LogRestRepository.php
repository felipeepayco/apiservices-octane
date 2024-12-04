<?php


namespace App\Repositories\Logrest;


use App\Models\LogRest;

class LogRestRepository
{
    public function store($data)
    {
        return LogRest::create($data);
    }
}