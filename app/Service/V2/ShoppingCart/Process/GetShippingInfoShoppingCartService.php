<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\BblBuyerRepository;
use \Illuminate\Http\Request;

class GetShippingInfoShoppingCartService extends HelperPago
{

    private $buyerRepository;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        Request $request,
        BblBuyerRepository $buyerRepository,
    ) {
        parent::__construct($request);
        $this->buyerRepository = $buyerRepository;

    }

    public function handle($params)
    {
        try {
            $fieldValidation = $params;
            $email = $fieldValidation["email"];
            $clientId = $fieldValidation["clientId"];

            $bblClientsInfoBuyer = $this->buyerRepository->findByClientIdAndEmail($clientId, $email);
            if ($bblClientsInfoBuyer) {
                $data = [
                    'firstName' => $bblClientsInfoBuyer->nombre,
                    'lastName' => $bblClientsInfoBuyer->apellido,
                    'documentId' => $bblClientsInfoBuyer->documento,
                    'email' => $bblClientsInfoBuyer->correo,
                    'phone' => $bblClientsInfoBuyer->telefono,
                    'address1' => $bblClientsInfoBuyer->direccion,
                    'address2' => $bblClientsInfoBuyer->otros_detalles,
                    'city' => $bblClientsInfoBuyer->ciudad,
                    'codeDane' => $bblClientsInfoBuyer->codigo_dane,
                    'departament' => $bblClientsInfoBuyer->departamento,
                    'country' => $bblClientsInfoBuyer->codigo_pais ?? "CO",
                ];
                $success = true;
                $title_response = 'Get shipping info';
                $text_response = 'Get shipping info';
                $last_action = 'get_shoppingcart_shipping_info';
            } else {
                $success = false;
                $title_response = 'Not found shipping data';
                $text_response = 'Not found shipping data';
                $last_action = 'get_shoppingcart_shipping_info';
                $data = [];
            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error get data shipping info";
            $last_action = 'fetch data from database';
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = ['totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage, 'aditionalData' => $exception];
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
}
