<?php

namespace App\Service;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EcontrolService
{
    /**
     * query para identificar si la regla o scoring que el cliente va editar pertenece a un template
     * @param int $clientId
     */
    public function isTemplate(int $clientId)
    {
        $queryBuilder = DB::table('ws_configuracion_cliente AS wcc');
        return $queryBuilder
            ->select('wcc.id', 'wcc.id_cliente', 'wcr.id AS id_regla', 'wp.id AS id_plantilla')
            ->join('ws_configuracion_regla AS wcr', 'wcc.id_configuracion_regla', '=', 'wcr.id')
            ->join('ws_plantilla AS wp', 'wcr.id', '=', 'wp.id_configuracion_regla')
            ->where('id_cliente', $clientId)
            ->where('wcc.activo', 1)
            ->first();
    }

    /**
     * query para obtener los ws filtros activos del cliente.
     * Info extra selects:
     * - automaticRejection : true, filtro de rechazo inmediato
     * - score: 100, si es un filtro de rechazo inmediato el score es 100 independiente de la conf del cliente
     * @param int $clientId
     * @param string $currency
     * @return Collection
     */
    public function wsFiltersClient(int $clientId, string $currency)
    {
        $subQuery = DB::table('ws_configuracion_cliente AS wcc');
        $subQuery
            ->select('wfc.*')
            ->join('ws_configuracion_regla AS wcr', 'wcc.id_configuracion_regla', '=', 'wcr.id')
            ->join('ws_filtros_configuracion AS wfc', function ($join) {
                $join->on('wfc.id_configuracion_regla', '=', 'wcr.id');
            })
            ->where('wcc.activo', 1)
            ->where('wcc.id_cliente', $clientId)
            ->where('wfc.activo', 1);

        $queryBuilder = DB::table('ws_filtros_control AS wf')->orderBy('wf.id');
        $filters = $queryBuilder
            ->select(
                'filters_active_client.id AS id_conf',
                'wft.nombre',
                'wf.id AS id_filtro',
                'wf.filtro',
                'wft.codigo',
                'wf.descripcion',
                DB::raw(
                    'CASE 
                        WHEN filters_active_client.valor IS NULL AND (wf.id = 62 OR wf.id = 61) THEN 5000
                        ELSE filters_active_client.valor
                    END as valor'
                ),
                DB::raw(
                    'CASE 
                        WHEN wf.scoring = 100 THEN 100
                        WHEN filters_active_client.score IS NULL THEN wf.scoring
                        ELSE filters_active_client.score
                    END AS score'
                ),
                DB::raw('IFNULL(filters_active_client.estado, 0) AS estado'),
                DB::raw('IFNULL(filters_active_client.orden, wf.orden) AS orden'),
                DB::raw(
                    'CASE 
                        WHEN filters_active_client.moneda IS NULL THEN "COP"
                        ELSE filters_active_client.moneda
                    END as moneda'
                ),
                DB::raw(
                    'CASE
                        WHEN wf.scoring = 100 THEN true
                        ELSE false
                    END AS automaticRejection'
                )
            )->join('ws_filtros_tipo AS wft', 'wf.id_tipo_filtro', '=', 'wft.id')
            ->leftJoinSub($subQuery, 'filters_active_client', function ($join) {
                $join->on('wf.id', '=', 'filters_active_client.id_filtro');
            })->where('wf.activo', 1)
            ->orderBy('wft.id')
            ->get();

            $arrId = [];
            foreach ($filters as $filtro) {
                array_push($arrId, $filtro->id_filtro);
            }
            $costes = $this->getCostos($clientId, $currency, $arrId);
                foreach ($filters as $filtro) {
                    $monto = 0;
                    $moneda = $currency;
                    foreach ($costes as  $costo) {
                        if ($costo->id_filtro == $filtro->id_filtro) {
                            $monto = $costo->monto;
                            $moneda  = isset($costo->moneda)?$costo->moneda:$currency;
                            break;
                        }
                    }
                    $filtro->costo = $monto;
                    $filtro->costo_moneda  = $moneda;
                }
                        
            return $filters;

    }

    /**
     * consulta a la view que ya esta armada en la BD.
     * @param $id_cliente
     * @return \Illuminate\Support\Collection
     */
    public function getCostos($id_cliente, $moneda, $arrId)
    {
        $subQueryCostoClienteID = DB::table('ws_filtros_costos AS wfct');
        $subQueryCostoClienteID
        ->select('wfct.id_filtro')
        ->whereIn("id_filtro", $arrId)
        ->Where('wfct.id_cliente', $id_cliente); 

        //Se obtienen todos los  campos de costos personalizados para la union con los filtros sin costos personalizados
        $subQueryCostoCliente = DB::table('ws_filtros_costos AS wfct');
        $subQueryCostoCliente
        ->select('wfct.*')
        ->whereIn("id_filtro", $arrId)
        ->Where('wfct.id_cliente', $id_cliente); 

        return DB::table('ws_filtros_costos AS wf')
        ->select('*')
        ->whereIn("id_filtro", $arrId)->where("moneda", $moneda)
        ->whereNotIn('id_filtro', $subQueryCostoClienteID)
        ->union($subQueryCostoCliente)
        ->get();         
        
    }
}