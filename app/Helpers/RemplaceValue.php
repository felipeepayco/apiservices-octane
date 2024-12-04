<?php
namespace App\Helpers;

class RemplaceValue
{

    public  static function getReplacementValues($key, $value) {
        $replacements = [
            'id' => 'id',
            'title' => 'titulo',
            'origin' => 'origen',
            'invoiceNumber' => 'numerofactura',
            'description' => 'descripcion',
            'categorieId' => 'categorias',
            'amount' => 'valor',
            'currency' => 'moneda',
            'tax' => 'iva',
            'baseTax' => 'base_iva',
            'discountPrice' => 'precio_descuento',
            'onePayment' => 'cobrounico',
            'quantity' => 'cantidad',
            'available' => 'disponible',
            'expirationDate' => 'fecha_expiracion',
            'urlResponse' => 'url_respuesta',
            'urlConfirmation' => 'url_confirmacion',
            'catalogueId' => 'catalogo_id',
            'contactName' => 'nombre_contacto',
            'contactNumber' => 'numero_contacto',
            'sales' => 'ventas',
            'outstanding' => 'destacado',
            'onlyActive' => 'activo'
        ];

        if ($key == 'id' || $key == 'categorieId') {
            $value = (int) $value;
        }
    
        return [
            'key' => $replacements[$key],
            'value' => $value,
        ];
    }

}
