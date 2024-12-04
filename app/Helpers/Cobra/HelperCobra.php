<?php

namespace App\Helpers\Cobra;

use App\Models\Clientes;
use App\Models\PasarelaConfig;
use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;


class HelperCobra extends HelperPago
{


    /**
     * Informacion del cliente
     *
     * @var Clientes
     */
    protected $client;


    /**
     * HelperCobra constructor.
     * @param Request $request
     * @param $id_cliente
     */
    public function __construct(Request $request, $id_cliente)
    {
        parent::__construct($request);
        $clientes = new Clientes();
        $this->client = $clientes->find($id_cliente);
    }

    /**
     * Funcion para validar el monto acumulado que lleva el cliente.
     * @param $amount
     * @return bool
     */
    public function validateAmounts($amount)
    {
        $clientId = $this->client->Id;

        $result_porcentaje = \DB::select("SELECT SUM(l0_.porcentaje) AS porcentaje FROM" . " lim_clientes_validacion l0_ WHERE l0_.cliente_id = '" . $clientId . "' AND" . " l0_.estado_id = 1");

        $porcentaje = (int)$result_porcentaje[0]->porcentaje;

        if ($porcentaje < 100 && $this->client->id_plan == 1010 && $this->client->fase_integracion == 2) {

            //Agregamos lo del valor acumulado
            $valor_acumulado = \DB::select("SELECT sum(valortotal) as total FROM transacciones where estado in ('Aceptada','Retenida') and enpruebas=2 and autorizacion!='000000' and facturable=1 and id_cliente={$clientId}");

            $total_acumulado = (int)$valor_acumulado[0]->total + (int)$amount;

            //Consultamos el maximo permitido de la bd
            $pasarela_config = PasarelaConfig::where('parametro', '=', 'maximo_acumulado_clientes_no_validados')->first();

            $maximo_permitido = (int)$pasarela_config->valor;

            return ($maximo_permitido > $total_acumulado);

        } else {
            return true;
        }
    }

}
