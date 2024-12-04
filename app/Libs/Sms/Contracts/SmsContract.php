<?php

namespace App\Libs\Sms\Contracts;

/**
 * SMS interface
 */
interface SmsContract
{
    /**
     * Send the given message to the given recipient.
     *
     * @return mixed
     */
    public function send();
}
