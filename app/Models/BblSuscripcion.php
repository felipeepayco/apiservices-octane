<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Common\SubscriptionStateCodes;
use Illuminate\Support\Facades\Log;

class BblSuscripcion extends Model
{
    protected $table = 'bbl_suscripciones';
    public $timestamps = false;

    protected $fillable = [
        "bbl_plan_id",
        "bbl_cliente_id",
        "factura_id",
        "fecha_inicio",
        "fecha_renovacion",
        "fecha_cancelacion",
        "estado",
        "fecha_creacion",
        "suscripcion_sdk_id",
        "notificacion",
        "created_at",
    ];

    public function plan()
    {
        return $this->belongsTo(BblPlan::class, 'bbl_plan_id', 'id');
    }

    public function cliente()
    {
        return $this->belongsTo(BblClientes::class, 'bbl_cliente_id', 'id');
    }

    public static function formatResponsePlan($dataSuscription) {

        $pendingSubscription = self::where("bbl_cliente_id", $dataSuscription->bbl_cliente_id)
        ->where("estado", 5)
        ->orderBy("created_at", "DESC")
        ->first();
        $rejectSubscription = self::where("bbl_cliente_id", $dataSuscription->bbl_cliente_id)
        ->where("estado", 3)
        ->where("notificacion", 0)
        ->orderBy("created_at", "DESC")
        ->first();


        return [
            "id" => $dataSuscription->id,
            "planId" => $dataSuscription->bbl_plan_id,
            "clienteId" => $dataSuscription->bbl_cliente_id,
            "fechaCreacion" => $dataSuscription->fecha_creacion . "T00:00:00-05:00",
            "fechaInicio" => $dataSuscription->fecha_inicio . "T00:00:00-05:00",
            "fechaRenovacion" => $dataSuscription->fecha_renovacion . "T00:00:00-05:00",
            "fechaCancelacion" => $dataSuscription->fecha_cancelacion . "T00:00:00-05:00",
            "estado" => $dataSuscription->estado,
            "notificacion"=>$dataSuscription->notificacion,
            "pendingPLan"=> $pendingSubscription,
            "rejectPlan"=> $rejectSubscription,
            "planRel" => [
                "id" => $dataSuscription->plan->id,
                "nombre" => $dataSuscription->plan->nombre,
                "planSuscripcionId" => $dataSuscription->plan->plan_suscripcion_id,
                "tiendas" => $dataSuscription->plan->tiendas,
                "productos" => $dataSuscription->plan->productos,
                "categorias" => $dataSuscription->plan->categorias,
                "analitica" => $dataSuscription->plan->analitica === 1 ? true : false,
                "estado" => $dataSuscription->plan->estado === 1 ? true : false,
                "fechaCreacion" => $dataSuscription->plan->fecha_creacion."T00:00:00-05:00",
                "periodicidad" => $dataSuscription->plan->periodicidad,
                "precio" => $dataSuscription->plan->precio,
                "__isInitialized__" => true
            ]
        ];
    }


    public static function checkPendingPlan($clientId)
    {
        $instance = new self();

        $qb = $instance
            ->select("bbl_suscripciones.*")
            ->where("bbl_suscripciones.bbl_cliente_id", "=", $clientId)
            ->where("bbl_suscripciones.estado", "=", SubscriptionStateCodes::INTEGRATION)
            ->orderBy("bbl_suscripciones.created_at", "DESC")
            ->first();

        return $qb ;

    }

    public static function checkPlanByDate($clientId, $productTypeId, $statusPC = [], $all = false, $priorities = false)
    {
        $date = Carbon::now();
        $response = [];
        $instance = new self();

        $qb = $instance
            ->select("bbl_suscripciones.*")
            ->leftJoin('bbl_planes', 'bbl_suscripciones.bbl_plan_id', '=', 'bbl_planes.id')
            ->where("bbl_suscripciones.bbl_cliente_id", "=", $clientId)
            ->where("bbl_suscripciones.fecha_creacion", "<=", $date->format("Y-m-d"))
            ->where("bbl_suscripciones.fecha_renovacion", ">=", $date->format("Y-m-d"));

        if (count($statusPC)) {
            $qb = $qb->whereIn("bbl_suscripciones.estado", $statusPC);
        }

        if ($priorities) {


            if(!$all)
            {
                $vf = clone $qb;
                $vf = $vf->orderBy("bbl_suscripciones.estado", "ASC");

                $activeSubscription= $vf->get()->first();
                //CHECK ACTIVE SUBSCRIPTION
                if(!empty($activeSubscription) && ($activeSubscription->estado==SubscriptionStateCodes::ACTIVE))
                {
                    $qb = $qb->orderBy("bbl_suscripciones.estado", "ASC");

                }else{
                    $qb = $qb->orderBy("bbl_suscripciones.created_at", "DESC");

                }

            }else
            {
                $qb = $qb->orderBy("bbl_suscripciones.created_at", "DESC");
              

            }


        }

        if (!$all) {
            $qb = $qb->take(1);
        }
        $result = $qb->get();

        if (!$result->isEmpty()) {
            $response = $result;
        }

        return $response;

    }

    public static function verifyPlan($clienteId, $activo = null)
    {
        $instance = new self();

        $qb = $instance
            ->select("bbl_suscripciones.*")
            ->leftJoin('bbl_planes', 'bbl_suscripciones.bbl_plan_id', '=', 'bbl_planes.id')
            ->where("bbl_suscripciones.bbl_cliente_id", "=", $clienteId)
            ->orderBy("bbl_suscripciones.id", "DESC")
            ->limit(1);

        if ($activo) {
            $qb = $qb->where("bbl_suscripciones.estado", "=", 1);
        }

        $result = $qb->get();

        $resultado = false;
        if (count($result)) {
            $resultado = $result[0];
        }
        return $resultado;
    }
}
