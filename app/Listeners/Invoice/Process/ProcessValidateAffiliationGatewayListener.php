<?php

namespace App\Listeners\Invoice\Process;

use App\Common\ProductClientStateCodes;
use App\Common\ProductosId;
use App\Events\Invoice\Process\ProcessInvoiceCreateEvent;
use App\Events\Invoice\Process\ProcessValidateAffiliationGatewayEvent;
use App\Exceptions\GeneralException;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Respuesta\GeneralResponse;
use App\Helpers\Validation\CommonValidation;
use App\Listeners\Services\BillService;
use App\Listeners\Services\ClientProductService;
use App\Models\ApifyClientes;
use App\Models\Clientes;
use App\Models\ConfAlliedBilling;
use App\Models\ProductosClientes;
use Illuminate\Http\Request;

class ProcessValidateAffiliationGatewayListener extends HelperPago
{
    /**
     * ProcessValidateAffiliationGatewayListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    private const TEXT_RESPONSE = "Factura consultada con éxito";
    private const LAST_ACTION = "Validate Affiliation Gateway";
    private const EXPIRATION_DAYS = 21;

    /**
     * @param ProcessValidateAffiliationGatewayEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(ProcessValidateAffiliationGatewayEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;

            $clientId = CommonValidation::getFieldValidation($fieldValidation, "clientId");


            $arr_respuesta = [];

            $this->validateEntityAllied($clientId,$arr_respuesta);

            if(!empty($arr_respuesta)){
                return $arr_respuesta;
            }

            $productClient = ProductosClientes::where("cliente_id", $clientId)->where("producto_id", ProductosId::AFILIACION_GATEWAY)->first();

            if ($productClient) {
                if ($productClient["estado"] === ProductClientStateCodes::ACTIVE || $productClient["estado"] === ProductClientStateCodes::PENDING) {
                    $data = [
                        "status" => $productClient["estado"]
                    ];

                    $arr_respuesta = GeneralResponse::response(true, self::TEXT_RESPONSE, self::LAST_ACTION, $data);
                } else {
                    $billService = new BillService();
                    $billUrl = $billService->generateUrl(getenv("PROJECT_ID_RECAUDO_FILE"), $productClient["id"]);

                    $data = [
                        "status" => $productClient["estado"],
                        "url" => $billUrl
                    ];
                    $arr_respuesta = GeneralResponse::response(true, self::TEXT_RESPONSE, self::LAST_ACTION, $data);
                }
            } else {
                if (date('Y-m-d') <= getenv('FREE_GATEWAY_MEMBERSHIP_TILL')) {
                    $this->handleCreateActiveClientProduct($clientId, $arr_respuesta);
                } else {
                    $this->handleInvoiceCreate($clientId, $arr_respuesta);
                }
            }

            return $arr_respuesta;
        } catch (GeneralException $generalException) {
            return GeneralResponse::response(false, $generalException->getMessage(), self::LAST_ACTION, $generalException->getData());
        }
    }

    private function handleInvoiceCreate($clientId, &$arr_respuesta)
    {
        $parameters = [
            "clientId" => getenv("CLIENT_ID_APIFY_PRIVATE"),
            "projectId" => getenv("PROJECT_ID_RECAUDO_FILE"),
            "clientIdentifier" => $clientId,
            "details" => [
                [
                    "productId" => ProductosId::AFILIACION_GATEWAY,
                    "productStatus" => ProductClientStateCodes::INTEGRATION,
                    "expirationDays" => self::EXPIRATION_DAYS
                ]
            ],
            "success" => true
        ];

        $invoiceCreate = event(
            new ProcessInvoiceCreateEvent($parameters)
        );

        if ($invoiceCreate[0]["success"]) {
            $data = [
                "status" => ProductClientStateCodes::INTEGRATION,
                "url" => $invoiceCreate[0]["data"]["bills"][0]["url"]
            ];
            $arr_respuesta = GeneralResponse::response(true, self::TEXT_RESPONSE, self::LAST_ACTION, $data);
        } else {
            $arr_respuesta = $invoiceCreate;
        }
    }

    private function handleCreateActiveClientProduct($clientId, &$arr_respuesta)
    {
        $clientProductService = new ClientProductService();
        $clientProduct = $clientProductService->createActiveClientProduct($clientId, ProductosId::AFILIACION_GATEWAY);
        if ($clientProduct) {
            $data = [
                "status" => $clientProduct->estado,
            ];
            $arr_respuesta = GeneralResponse::response(true, self::TEXT_RESPONSE, self::LAST_ACTION, $data);
        } else {
            $arr_respuesta = GeneralResponse::response(false, "Fallo la creación del producto cliente", self::LAST_ACTION, []);
        }
    }

    private function validateEntityAllied(string $clientId, &$response)
    {
        $alliedEntity = ApifyClientes::select('apify_cliente_id')->where('cliente_id', $clientId)->first();
        $generalResponse = GeneralResponse::response(true, self::TEXT_RESPONSE, self::LAST_ACTION, [
            "status" => 1
        ]);
        
        if (!is_null($alliedEntity) && $alliedEntity->apify_cliente_id != getenv('CLIENT_ID_APIFY_PRIVATE')) {
            // A los comercios creados bajo entidad_alida_plus distinto a 4877 no se le debe generar la proforma
            // pero puede ocurrir una excepción configurada en la tabla 'config_allied_billing'
            $confAlliedBilling = ConfAlliedBilling::where('client_id', $alliedEntity->apify_cliente_id)->first();
            
            if (!$confAlliedBilling || ($confAlliedBilling && !$confAlliedBilling->charge_enable)) {
                $response = $generalResponse;
            }
        } else {
            // A los comercios creados bajo entidad_alida_plus 4877 se le debe generar la proforma
            // pero todo comercio que sea registrado con un aliado distinto a 4877 y este bajo la entidad_plus de 4877 no se le va a cobrar
            $client = Clientes::find($clientId);
            
            if(!is_null($client->id_aliado) && $client->id_aliado != getenv('CLIENT_ID_APIFY_PRIVATE') ){
                // como se explico anteriormente si tiene aliado distinto a 4877 no se le cobra a generar la proforma  
                // a excepción que este esté en la tabla 'config_allied_billing' indicando que le cobre
                $confAlliedBilling = ConfAlliedBilling::where('client_id', $client->id_aliado)->first();

                if (!$confAlliedBilling || ($confAlliedBilling && !$confAlliedBilling->charge_enable)) {
                    $response = $generalResponse;
                }
            }
        }
    }

}

