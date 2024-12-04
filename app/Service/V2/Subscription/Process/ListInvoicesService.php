<?php
namespace App\Service\V2\Subscription\Process;
use App\Models\BblSuscripcionCargos;

class ListInvoicesService{

    public function process($fieldValidation){

            $clientId   = $fieldValidation["clientId"];
            $page       = $fieldValidation["page"];
            $pageSize   = $fieldValidation["limit"];
            $fieldsView=[
                'bbl_suscripcion_cargos.id',
                'bbl_suscripcion_cargos.fecha',
                'bbl_suscripcion_cargos.descripcion',
                'bbl_suscripcion_cargos.tarjeta_franquicia',
                'bbl_suscripcion_cargos.tarjeta_nro',
                'bbl_suscripcion_cargos.valor_neto',
                'periodicidad',
            ];
            $bblSuscripcionCargos= BblSuscripcionCargos::join("bbl_planes","bbl_planes.plan_suscripcion_id","=","bbl_suscripcion_cargos.descripcion")->join("bbl_clientes","bbl_clientes.cliente_sdk_id","=","bbl_suscripcion_cargos.suscripcion_cliente_id")->where("bbl_clientes.id",$clientId)->orderBy('bbl_suscripcion_cargos.id','DESC')->paginate($pageSize,$fieldsView,'bbl_suscripcion_cargos.id',$page);
            if($bblSuscripcionCargos->count()>0){
                $bblSuscripcionCargosRows=$bblSuscripcionCargos;
                foreach($bblSuscripcionCargosRows as $cod=>$row){
                    $data[$cod]['id']               =$row->id;
                    $data[$cod]['fecha']            =$row->fecha;
                    $data[$cod]['descripcion']      =$row->descripcion;
                    $data[$cod]['periodo_pago_inicio']=$row->fecha;
                    $data[$cod]['periodo_pago_fin'] =$this->sumDate($row->fecha,$row->periodicidad);
                    $data[$cod]['forma_pago']       =$row->tarjeta_franquicia;  //??
                    $data[$cod]['tarjeta_nro']      =$row->tarjeta_nro;  //??
                    $data[$cod]['total']            =$row->valor_neto;

                }

                $success                = true;
                $textResponse           = "Consulta realizada exitosamente";
                $arr_respuesta['data']  = $this->paginationAndData($data,$bblSuscripcionCargos);
            }else{
                $arr_respuesta['data']  = [];
            }
            
            $arr_respuesta['success']   = $success ?? true;
            $arr_respuesta['textResponse'] = $textResponse  ?? "El usuario no tiene detalles de facturacion";


        return $arr_respuesta;

    }
    public function paginationAndData($data,$bblSuscripcionCargos) {
        return [
            "data"=>$data,
            "pagination"=>[
                "current_page" => $bblSuscripcionCargos->currentPage(),
                "last_page"=> $bblSuscripcionCargos->lastPage(),
                "next_page"=> $bblSuscripcionCargos->currentPage()+1,
                "per_page"=> $bblSuscripcionCargos->perPage(),
                "prev_page"=> $bblSuscripcionCargos->currentPage()<=2 ? null
                    : $bblSuscripcionCargos->currentPage()-1,
                "total"=> $bblSuscripcionCargos->total()
            ]
        ];
    }


    private function sumDate($fecha_actual,$month){
        $fecha_mas_un_mes = strtotime($fecha_actual . "+ $month month");
        return date("Y-m-d H:i:s", $fecha_mas_un_mes);
    }
}