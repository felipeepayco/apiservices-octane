<?php
namespace App\Service\V2\ShoppingCart\Process;

use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Listeners\Services\LogisticaService;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use App\Repositories\V2\ShoppingCartRepository;
use \Illuminate\Http\Request;

class LoadPickupShoppingCartService extends HelperPago
{

    private $shopping_cart_repository;
    private $catalogue_repository;
    private $client_repository;

    public function __construct(
        Request $request,
        ShoppingCartRepository $shopping_cart_repository,
        CatalogueRepository $catalogue_repository,
        ClientRepository $client_repository,
    ) {
        parent::__construct($request);

        $this->shopping_cart_repository = $shopping_cart_repository;
        $this->catalogue_repository = $catalogue_repository;
        $this->client_repository = $client_repository;
    }

    public function handle($params)
    {
        try {

            $fieldValidation = $params;

            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];
            $operator = $fieldValidation["operator"];
            $date = $fieldValidation["date"];
            $note = isset($fieldValidation["note"]) ? $fieldValidation["note"] : "";

            $clientData = $this->client_repository->find($clientId);

            //Validar que exista el carrito
            $shoppingCart = $this->searchShoppingCart($id, $clientId);

            if ($shoppingCart) {
                $catalogue = $this->catalogue_repository->findById($shoppingCart->catalogo_id);

                //logica para crear guia y recogida
                $epaycoReference = $this->findRefEpayco($shoppingCart);
                $quote = isset($shoppingCart->cotizacion) ? (array) $shoppingCart->cotizacion : null;
                $guide = isset($shoppingCart->guia) ? (array) $shoppingCart->guia : [];
                $pickup = [];
                $this->loginElogistica();
                $guideSuccess = ["status" => true];
                $logisticaService = new LogisticaService();
                if ($operator === '472') {
                    $guideSuccess = $logisticaService->handleGuideGeneration($shoppingCart, $operator, $catalogue, $epaycoReference, $quote[$operator], $quote[472] !== null ? $quote[472]->id_servico : 1, $guide, $note, $clientData['pagweb']);
                } else {
                    $pickup[$operator] = $this->handleProgramPickup($shoppingCart, $operator, $catalogue, $guide[$operator], $note, $date);
                }

                $shoppingCart["estado_entrega"] = "envio_programado";
                if (empty($pickup)) {
                    $shoppingCart->guia = $guide;
                } else {
                    $shoppingCart->entrega = $pickup;
                }
                if ($shoppingCart->save()) {
                    $success = true;
                    $title_response = 'pickup Shopping cart successfull';
                    $text_response = 'pickup Shopping cart successfull';
                    $last_action = 'pickup_shoppingcart';
                    $data = empty($pickup) ? $guide : $pickup;
                } else {
                    $success = false;
                    $title_response = 'Register shoppingcart pickup failed';
                    $text_response = 'Register shoppingcart pickup failed';
                    $last_action = 'register_shoppingcart_pickup';
                    $data = $guideSuccess;
                }

            } else {
                $success = false;
                $title_response = 'Shopping cart not found';
                $text_response = 'Shopping cart not found';
                $last_action = 'consult_shopping_cart';
                $data = [];
            }

        } catch (\Exception $exception) {
            $success = false;
            $title_response = 'Error ' . $exception->getMessage();
            $text_response = "Error program pickup shopping cart";
            $last_action = 'fetch data from database' . $exception->getLine();
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    public function searchShoppingCart($id, $clientId)
    {

        return $this->shopping_cart_repository->findByIdAndClient($id, $clientId, 'pagado');
    }

    public function findRefEpayco($shoppingCartData)
    {
        foreach ($shoppingCartData->pagos as $item) {
            if ($item["estado"] === "Aceptada") {
                return $item["referencia_epayco"];
            }
        }
        return "";
    }
    public function handleProgramPickup($shoppingCart, $operator, $catalogue, $guide, $note, $date)
    {
        $time = time();
        $bodyPickup = [
            "operador" => $operator,
            "id_operacion_epayco" => $guide->data->id_operacion_epayco,
            "id_configuracion" => $catalogue->configuracion_recogida_id,
            "fecha_recogida" => $date,
            "hora_inicial_recogida" => date("a", $time) === "am" ? "09:00:00" : "12:00:00",
            "hora_final_recogida" => date("a", $time) === "am" ? "12:00:00" : "19:00:00",
            "observaciones" => $note,
        ];
        $response = $this->elogisticaRequest($bodyPickup, "/api/v1/recogida");
        $response["fecha_registro"] = date("d/m/Y h:i:s a", $time);
        return $response;
    }
}
