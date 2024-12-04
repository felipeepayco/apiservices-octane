<?php
namespace App\Listeners\Catalogue\Process\DiscountCode;

use App\Events\Catalogue\Process\DiscountCode\CatalogueActivateInactivateDiscountCodeEvent;
use App\Exceptions\GeneralException;
use App\Models\BblDiscountCode;
use Illuminate\Http\Request;

class CatalogueActivateInactivateDiscountCodeListener
{

    /**
     * Create the event listener.
     *
     * @return void
     */

    private $discount_code;

    public function __construct(Request $request, BblDiscountCode $discount_code)
    {
        $this->discount_code = $discount_code;

    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(CatalogueActivateInactivateDiscountCodeEvent $event)
    {
        try {

            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];

            $discount_code = $this->discount_code->find($fieldValidation["id"]);
            $discount_code->estado = $fieldValidation["status"];
            $data = $discount_code->save();
            if ($data) {

                $success = true;
                $title_response = 'Discount code state changed successfully';
                $text_response = 'successful consult';
                $last_action = 'successful consult';
                $data = $data;
            } else {
                $success = false;
                $title_response = 'An error has ocurred, please try again';
                $text_response = 'An error has ocurred';
                $last_action = 'An error has ocurred';
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
