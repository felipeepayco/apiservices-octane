<?php

namespace App\Events\Econtrol\Validation;
/**
 * Description of ValidationWsCreateListEvent
 *
 * @author Jose Agraz
 */
class ValidationWsCreateListEvent
{
    /**
     * @var array
     */
    public $parameters;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
}