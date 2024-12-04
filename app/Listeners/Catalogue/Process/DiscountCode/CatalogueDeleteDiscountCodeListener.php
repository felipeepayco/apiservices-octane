<?php
namespace App\Listeners\Catalogue\Process\DiscountCode;

use App\Events\Catalogue\Process\DiscountCode\CatalogueDeleteDiscountCodeEvent;
use App\Exceptions\GeneralException;
use App\Models\BblDiscountCode;

class CatalogueDeleteDiscountCodeListener
{

    /**
     * Create the event listener.
     *
     * @return void
     */

    public function __construct()
    {}

    /**
     * Handle the event.
     * @return void
     */
    public function handle(CatalogueDeleteDiscountCodeEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];

            $data = BblDiscountCode::destroy($fieldValidation["id"]);

            if ($data) {

                $success = true;
                $title_response = 'Discount code deleted successfully';
                $text_response = 'successful consult';
                $last_action = 'successful consult';
                $data = $data;
            } else {
                $success = false;
                $title_response = 'Discount code not found';
                $text_response = 'successful consult';
                $last_action = 'successful consult';
                $data = $data;
            }

        } catch (GeneralException $generalException) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = $generalException->getMessage();
            $arr_respuesta['textResponse'] = $generalException->getMessage();
            $arr_respuesta['lastAction'] = "generalException";
            $arr_respuesta['data'] = $generalException->getData();

            return $arr_respuesta;
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

}
