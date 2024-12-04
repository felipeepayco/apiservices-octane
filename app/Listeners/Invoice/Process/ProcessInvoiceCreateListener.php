<?php

namespace App\Listeners\Invoice\Process;

use App\Common\ProductClientStateCodes;
use App\Events\Billcollect\Process\ProcessCreateBillEvent;
use App\Events\Billcollect\Process\ProcessViewConfigProyectEvent;
use App\Events\Invoice\Process\ProcessInvoiceCreateEvent;
use App\Exceptions\GeneralException;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Respuesta\GeneralResponse;
use App\Helpers\Validation\CommonValidation;
use App\Listeners\Services\BillService;
use App\Models\Clientes;
use App\Models\Municipios;
use App\Models\Productos;
use App\Models\ProductosClientes;
use App\Models\ProductosEstados;
use App\Models\RecaudoFacturasLote;
use App\Models\TipoDocumentos;
use Illuminate\Http\Request;

class ProcessInvoiceCreateListener extends HelperPago
{
    /**
     * ProcessInvoiceCreateListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    private const RFL_FECHA = "recaudo_facturas_lote.fecha";

    /**
     * @param ProcessInvoiceCreateEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(ProcessInvoiceCreateEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;

            $clientId = CommonValidation::getFieldValidation($fieldValidation, "clientId");
            $projectId = CommonValidation::getFieldValidation($fieldValidation, "projectId");
            $clientIdentifier = CommonValidation::getFieldValidation($fieldValidation, "clientIdentifier", 0);
            $details = CommonValidation::getFieldValidation($fieldValidation, "details", []);
            $dataCreateInvoice = ["projectId" => $projectId, "clientId" => $clientId, "bill" => [], "lotId" => 0];
            $arr_respuesta = [];

            /* Validación de la configuración previa del proyecto recaudo archivo */
            $processViewConfigProyect = event(
                new ProcessViewConfigProyectEvent([
                    "clientId" => $clientId,
                    "projectId" => $projectId,
                    "success" => true
                ])
            );

            if ($processViewConfigProyect[0]["success"]) {
                /* Capturar el lote de facturas actual */
                $this->getLotByDate($clientId, $projectId, $dataCreateInvoice);

                /* Validación de existencia del estado del producto */
                $this->validateStateProducts($details);

                /* Seteo detalles de la fatura(s) y se guarda el registro ProductClients */
                $this->setInvoiceDetails($details, $clientIdentifier, $dataCreateInvoice);

                $processCreateBill = event(
                    new ProcessCreateBillEvent($dataCreateInvoice)
                );

                if ($processCreateBill[0]["success"]) {
                    $arr_respuesta = GeneralResponse::response(true, "Factura creada con éxito", "Invoice Create", $processCreateBill[0]["data"]);
                } else {
                    $arr_respuesta = $processViewConfigProyect;
                }
            } else {
                $arr_respuesta = $processViewConfigProyect[0];
            }

            return $arr_respuesta;
        } catch (GeneralException $generalException) {
            return GeneralResponse::response(false, $generalException->getMessage(), "Invoice Create General Exception", $generalException->getData());
        }
    }

    private function getLotByDate($clientId, $idProyecto, &$dataCreateInvoice)
    {
        $date = new \DateTime("now");

        $recaudoFacturasLote = RecaudoFacturasLote::select("recaudo_facturas_lote.*")
            ->join("recaudo_proyecto", "recaudo_proyecto.configuracion_general_id", "recaudo_facturas_lote.configuracion_general_id")
            ->where(self::RFL_FECHA, ">=", "{$date->format("Y-m-01")}")
            ->Where(self::RFL_FECHA, "<=", "{$date->format("Y-m-31")}")
            ->Where("recaudo_facturas_lote.id_cliente", "{$clientId}")
            ->Where("recaudo_proyecto.id", "{$idProyecto}")
            ->orderByDesc(self::RFL_FECHA)
            ->first();

        if (!is_null($recaudoFacturasLote)) {
            $dataCreateInvoice["lotId"] = $recaudoFacturasLote["id"];
        } else {
            $dataCreateInvoice["lotId"] = 0;
        }
    }

    private function validateStateProducts($details)
    {
        $productStatus = ProductosEstados::where('id', '!=', ProductClientStateCodes::ACTIVE)->get();
        $productStatus = array_values(array_column($productStatus->toArray(), 'id'));

        foreach ($details as $detail) {
            if (!isset($detail["productStatus"]) || !in_array($detail["productStatus"], $productStatus)) {
                throw  new GeneralException("Invalid product " . $detail["productId"] . " status", ['codError' => 500, 'errorMessage' => "Invalid product " . $detail["productId"] . " status"]);
            }
        }
    }

    private function setInvoiceDetails($details, $clientIdentifier, &$dataCreateInvoice)
    {
        $currentDate = date("d-m-Y");
        $dateTime = new \DateTime($currentDate);
        $billService = new BillService();

        foreach ($details as $detail) {
            $product = Productos::where('id', $detail['productId'])->first();
            $dateTime2 = new \DateTime(date("d-m-Y", strtotime($currentDate . "+{$product->periodicidad} month")));

            $clientProduct = new ProductosClientes();
            $clientProduct->cliente_id = $clientIdentifier;
            $clientProduct->fecha_creacion = $dateTime;
            $clientProduct->fecha_inicio = $dateTime;
            $clientProduct->fecha_renovacion = $dateTime2;
            $clientProduct->fecha_cancelacion = $dateTime2;
            $clientProduct->producto_id = $detail['productId'];
            $clientProduct->fecha_periodo = $dateTime;
            $clientProduct->periocidad = $product->periodicidad;
            $clientProduct->precio = $product->precio;
            $clientProduct->estado = $detail['productStatus'];
            $clientProduct->save();

            $client = Clientes::where('id', $clientIdentifier)->first();
            $documentType = TipoDocumentos::where("id", $client->tipo_doc)->first();
            $municipios = Municipios::where("id", $client->id_ciudad)->first();
            $dateTime3 = new \DateTime(date("d-m-Y", strtotime($currentDate . "+{$detail['expirationDays']} days")));

            $taxAndRetentions = $billService->calculateTaxAndRetentions($client,$product->precio);

            $dataBill = [];
            $dataBill["expirationDateFirst"] = $dateTime3->format("Y-m-d 23:59:59");
            $dataBill["companyIdentification"] = $clientIdentifier;
            $dataBill["names"] = $client->razon_social ? $client->razon_social : $client->nombre_empresa;
            $dataBill["typeDoc"] = $documentType->nombre;
            $dataBill["document"] = $client->documento;
            $dataBill["email"] = $client->email;
            $dataBill["additionalFirst"] = $municipios? $municipios->nombre: "";
            $dataBill["additionalSecond"] = $client->direccion? $client->direccion: 0;
            $dataBill["phoneNumber"] = $client->celular;
            $dataBill["amountFirst"] = $product->precio + $taxAndRetentions["iva"] - $taxAndRetentions["reteiva"] - $taxAndRetentions["retefuente"];
            $dataBill["additionalThird"] = $clientProduct["id"];
            $dataBill["additionalFourth"] = $product->precio;
            $dataBill["additionalFifth"] = 0;
            $dataBill["additionalSixth"] = $product->precio;
            $dataBill["additionalSeventh"] = $taxAndRetentions["retefuente"];
            $dataBill["additionalEighth"] = $taxAndRetentions["reteiva"];
            $dataBill["taxFirst"] = $taxAndRetentions["iva"];
            $dataBill["descriptionFirst"] = $product->nombre;

            array_push($dataCreateInvoice["bill"], $dataBill);
        }
    }
}
