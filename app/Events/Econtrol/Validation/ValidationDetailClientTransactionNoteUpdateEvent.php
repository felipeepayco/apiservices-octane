<?php


namespace App\Events\Econtrol\Validation;

class ValidationDetailClientTransactionNoteUpdateEvent
{
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}