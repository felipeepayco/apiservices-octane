<?php

namespace App\Repositories\Transaction;

use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    public function getCashTransactionById($params)
    {
        return DB::table('transacciones')
            ->select([
                'transacciones.Id',
                'transacciones.valortotal',
                'transacciones.descripcion_producto',
                'transacciones.fechaexpiracion',
                'detalle_transacciones.emaild',
                'transacciones.id_cliente'
            ])
            ->where([
                ['transacciones.Id', $params['ref_payco']],
                ['transacciones.estado', 'Pendiente']
            ])
            ->whereIn('transacciones.franquicia', ['BA', 'RS'])
            ->join('detalle_transacciones', 'transacciones.Id', '=', 'detalle_transacciones.pago')
            ->first();
    }

    public function updateTransaction($ref_payco, $data)
    {
        return DB::table('transacciones')
            ->where('Id', $ref_payco)
            ->whereIn('franquicia', ['BA', 'RS'])
            ->update($data);

    }

    public function findTransaction($params)
    {
        return DB::table('transacciones')
            ->select([
                'Id',
                'estado',
                'cod_respuesta',
                'respuesta',
                'enpruebas',
                'id_cliente'
            ])
            ->where([
                ['Id', $params['ref_payco']],
                ['estado', 'Pendiente']
            ])
            ->whereIn('franquicia', ['BA', 'RS'])
            ->first();
    }
}