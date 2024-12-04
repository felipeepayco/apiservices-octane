<?php

namespace App\Libs\Sms\Drivers;

use App\Libs\Sms\Contracts\SmsContract;

/**
 * Driver class
 */
abstract class Driver implements SmsContract
{
    /**
     * The recipient of the message.
     *
     * @var string
     */
    protected $recipient;

    /**
     * The message to send.
     *
     * @var string
     */
    protected $message;

    /**
     * Set the recipient of the message.
     *
     * @param string  $recipient
     * @return $this
     */
    public function to(string $recipient)
    {
        if (!$recipient) {
            throw new \Exception('El parámetro [to] es obligatorio.');
        }

        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Set the content of the message.
     *
     * @param string  $message
     * @return $this
     */
    public function message(string $message)
    {
        if (empty($message)) {
            throw new \Exception('El parámetro [menssage] es obligatorio');
        }

        $this->message = $message;

        return $this;
    }

    /**
     * Set the options for config function
     *
     * @param array $options
     * @return array
     */
    abstract public function options(array $options);

    /**
     * Send message function
     *
     * @return JsonResponse
     */
    abstract public function send();
}
