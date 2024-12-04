<?php

namespace App\Libs\Sms;

use App\Libs\Sms\Drivers\HablamecoDriver;
use Illuminate\Support\Manager;

class SmsManager extends Manager
{

    /**
     * Get a driver instance.
     *
     * @param string $name
     * @return string
     */
    public function channel($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Get the default SMS driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['sms.default'];
    }

    /**
     * Create a HablameCo SMS driver instance.
     *
     * @return HablameCoDriver
     */
    public function createHablamecoDriver()
    {
        return new HablameCoDriver(
            $this->app['config']['sms.hablameco.account'],
            $this->app['config']['sms.hablameco.apiKey'],
            $this->app['config']['sms.hablameco.token'],
        );
    }
}
