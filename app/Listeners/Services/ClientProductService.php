<?php


namespace App\Listeners\Services;

use App\Common\ProductClientStateCodes;
use App\Exceptions\GeneralException;
use App\Models\Productos;
use App\Models\ProductosClientes;

class ClientProductService
{
    public function createActiveClientProduct($clientId, $productId)
    {
        try {
            $currentDate = date("Y-m-d");
            $dateTime = new \DateTime($currentDate);
            $product = Productos::where('id', $productId)->first();
            $dateTime2 = new \DateTime(date("Y-m-d", strtotime($currentDate . "+{$product->periodicidad} month")));

            $clientProduct = new ProductosClientes();
            $clientProduct->cliente_id = $clientId;
            $clientProduct->fecha_creacion = $dateTime;
            $clientProduct->fecha_inicio = $dateTime;
            $clientProduct->fecha_renovacion = $dateTime2;
            $clientProduct->fecha_cancelacion = $dateTime2;
            $clientProduct->producto_id = $productId;
            $clientProduct->fecha_periodo = $dateTime2;
            $clientProduct->periocidad = $product->periodicidad;
            $clientProduct->precio = $product->precio;
            $clientProduct->estado = ProductClientStateCodes::ACTIVE;
            $clientProduct->save();

            return $clientProduct;
        } catch (GeneralException $generalException) {
            return false;
        }
    }
}
