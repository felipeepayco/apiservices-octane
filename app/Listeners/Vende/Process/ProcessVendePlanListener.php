<?php
namespace App\Listeners\Vende\Process;

use App\Events\Vende\Process\ProcessVendePlanEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate as Validate;
use App\Models\BblPlan;
use App\Models\BblSuscripcion;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProcessVendePlanListener extends HelperPago
{
    const CODIGO = "codigo";
    const VALOR = "valor";
    const PERIODICITY = "periodicity";
    const ANNUAL = "annual";
    const MONTHLY = "monthly";
    const VALUE = "value";
    const LAST_PRODUCT = "lastProduct";
    const DATE_FORMAT_ONE = "Y-m-d";
    const PLAN_ID = "planId";
    const PRODUCT_ID = "productId";
    const STATUS = "status";
    const DATE_CANCEL = "dateCancel";
    const TOTAL = "total";
    const URL_PAYMENT = "urlPayment";
    const PRODUCT_IN_INTEGRATION = "productInIntegration";
    const DATE_FORMATE_TWO = "d/m/Y";

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ProcessVendePlanEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;

            $clientId = $fieldValidation["clientId"];
            $tipoProductoId = $fieldValidation["tipo_producto_id"];
            $suscriptions = BblSuscripcion::checkPlanByDate($clientId, $tipoProductoId, [1, 2, 5, 10], true);

            $data = [
                self::PERIODICITY => [self::ANNUAL => [], self::MONTHLY => []],
                "configProductType" => null,
                self::PLAN_ID => count($suscriptions) ? $suscriptions[0]->id : null,
                "discount" => ["type" => self::VALOR, self::VALUE => 0],
                "plan" => [],
                self::LAST_PRODUCT => [],
            ];

            if (count($suscriptions)) {

                foreach ($suscriptions as &$suscription) {
                    switch ($suscription->estado) {
                        case 1:
                            //Producto activo
                            $this->clientVendeProducts($data, $suscription, "plan", "Activo");
                            break;
                        case 2:

                            //Producto en pendiente con un producto activo
                            $this->clientVendeProducts($data, $suscription, "productPending", "Pendiente");
                            $data['productPending'][self::URL_PAYMENT] = "";
                            break;
                        case 5:
                            //Producto en integración con un producto activo
                            $this->clientVendeProducts($data, $suscription, self::PRODUCT_IN_INTEGRATION, "Integracion");
                            $data[self::PRODUCT_IN_INTEGRATION][self::URL_PAYMENT] = "";
                            break;
                        case 10:

                            //Producto pendiente activación con producto activo
                            $this->clientVendeProducts(
                                $data, $suscription, "productPendingOfActivate", "Pendiente Activación"
                            );
                            break;
                        default:
                            break;
                    }
                }
            } else {
                // Ultimo producto
                $ultimaSuscripcion = BblSuscripcion::verifyPlan($clientId);
                if ($ultimaSuscripcion && $ultimaSuscripcion->estado == 3) {
                    $this->clientVendeProducts($data, $ultimaSuscripcion, self::LAST_PRODUCT, "Cancelado");
                }
            }

            //Productos mensuales
            $this->productsVende(count($suscriptions) ? $suscriptions[0] : $suscriptions, $data, self::MONTHLY, 1);

            //Productos anuales
            $this->productsVende(count($suscriptions) ? $suscriptions[0] : $suscriptions, $data, self::ANNUAL, 12);

            $code = 200;
            $message = "Consulta de planes";
            $paginate_info = null;
            $status = true;

        } catch (\Exception $exception) {

            $code = 404;
            $message = $exception->getMessage();
            $status = false;
            $paginate_info = null;
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }

        $arr_respuesta['code'] = $code;
        $arr_respuesta['message'] = $message;
        $arr_respuesta['paginate_info'] = $paginate_info;
        $arr_respuesta['status'] = $status;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function productsVende($suscription, &$data, $position, $periodicidad)
    {
        $arPlans = BblPlan::where(["periodicidad" => $periodicidad, "estado" => 1])->get();
        foreach ($arPlans as $plan) {

            $dataAnnual = [
                self::PRODUCT_ID => $plan->id,
                "description" => $plan->nombre,
                "nameProduct" => $plan->nombre,
                "priceProduct" => $plan->precio,
                "config" => [],
                "recommended" => true,
                "productIdSelected" => isset($suscription->bbl_plan_id) ? $plan->id == $suscription->bbl_plan_id : false,
                "planSuscripcionId" => $plan->plan_suscripcion_id,
            ];

            if ($periodicidad == 12) {
                $dataAnnual["priceDiscount"] = $plan->precio;
            }

            array_push($data[self::PERIODICITY][$position], $dataAnnual);
        }

    }

    private function clientVendeProducts(&$data, $suscription, $position, $status)
    {
        $dateNow = Carbon::now();
        $dateNow->format(self::DATE_FORMAT_ONE);

        $valorPagoPlan = 0;

        if ($suscription) {
            $valorPagoPlan = $suscription->plan->precio;
        }
        $data[$position] = [
            self::PLAN_ID => $suscription->id,
            self::PRODUCT_ID => $suscription->bbl_plan_id,
            self::STATUS => $status,
            "name" => $suscription->plan->nombre,
            "dateDiff" => $dateNow->diff($suscription->fecha_creacion)->days,
            "dateCreate" => Carbon::parse($suscription->fecha_creacion)->format(self::DATE_FORMATE_TWO),
            "dateExpired" => Carbon::parse($suscription->fecha_inicio)->format(self::DATE_FORMATE_TWO),
            "dateRenovation" => Carbon::parse($suscription->fecha_renovacion)->format(self::DATE_FORMATE_TWO),
            self::DATE_CANCEL => $suscription->fecha_cancelacion ?
            Carbon::parse($suscription->fecha_cancelacion)->format(self::DATE_FORMATE_TWO)
            : null,
            self::TOTAL => $valorPagoPlan,
            self::URL_PAYMENT => null,
            self::PERIODICITY => $suscription->plan->periodicidad == 1 ? "Mensual" : "Anual",
        ];

    }

}
