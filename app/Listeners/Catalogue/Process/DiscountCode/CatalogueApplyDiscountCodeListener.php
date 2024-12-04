<?php
namespace App\Listeners\Catalogue\Process\DiscountCode;

use App\Events\Catalogue\Process\DiscountCode\CatalogueApplyDiscountCodeEvent;
use App\Helpers\Pago\HelperPago;
use App\Models\BblDiscountCode;
use Illuminate\Http\Request;

class CatalogueApplyDiscountCodeListener extends HelperPago
{

    /**
     * Create the event listener.
     *
     * @return void
     */

    private $bblDiscountCode;

    public function __construct(Request $request)
    {}

    /**
     * Handle the event.
     * @return void
     */
    public function handle(CatalogueApplyDiscountCodeEvent $event)
    {
        $bblDiscountCode = BblDiscountCode::select(
            "nombre as name",
            "tipo_descuento as discountType",
            "monto_descuento as discountAmount",
            "filtro_cantidad as filterAmount",
            "cantidad as quantity",
            "cantidad_restante as remainingQuantity",
            "filtro_periodo as filterTimeframe",
            "fecha_inicio as startDate",
            "fecha_fin as endDate",
            "filtro_categoria as filterCategory",
            "categorias as categories",
            "filtro_carro_compra as filterShopping",
            "monto_carro_compra as shoppingCarAmount",
            "estado as status",
            "combinar_codigo as combineCode",
            "cliente_id as clientId",
            'created_at as createdAt',
            'updated_at as updatedAt',
            'deleted_at as deletedAt'
        )->find($event->arr_parametros["bblDiscountCodeId"]);

        $arr_respuesta['success'] = true;
        $arr_respuesta['titleResponse'] = "Exito";
        $arr_respuesta['textResponse'] = "Exito";
        $arr_respuesta['lastAction'] = "Aplicar descuento";
        $arr_respuesta['data'] = $bblDiscountCode;

        return $arr_respuesta;

    }

}
