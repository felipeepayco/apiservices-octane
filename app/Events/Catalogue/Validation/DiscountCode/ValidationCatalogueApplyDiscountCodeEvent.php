<?php
namespace App\Events\Catalogue\Validation\DiscountCode;
use App\Events\Event;

/**
 * Description of ValidationCatalogueApplyDiscountCodeEvent
 *
 * @author Efrain
 */
class ValidationCatalogueApplyDiscountCodeEvent extends Event{
    public $arr_parametros;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($arr_parametros)
    {
        $this->arr_parametros = $arr_parametros;
    }
}
