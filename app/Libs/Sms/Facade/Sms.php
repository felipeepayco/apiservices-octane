<?php

namespace App\Libs\Sms\Facade;

use Illuminate\Support\Facades\Facade;

class Sms extends Facade
{
    /**
     * Get the registered name of the class.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'sms';
    }
}
