<?php


namespace App\Helpers\Proforma;

class FormatFields {
    
    public $data;
    public $type;
    public $dataClient;
    public $rettype;
    public $pcttype; 
    
    static function format($data, $type, $dataClient){

        $response = array();
        $prefix = "";

        if($type === "invoice"){
            $prefix = "I";
        }else if($type === "proforma"){
            $prefix = "P";
        }
        
        foreach($data as $singleField){


            $response[] = array(
                "id" => $singleField->Id,
                "id_client" => $dataClient->Id, 
                "document" => $dataClient->documento,
                "name" => $dataClient->nombre ." ". $dataClient->apellido,
                "lastName" => $dataClient->apellido,
                "email" => $dataClient->email,
                "city" => $dataClient->ciudad,
                "address" => $dataClient->direccion,
                "phone" => $dataClient->celular,
                "date" => $singleField->fecha,
                "deadline" => $singleField->fecha_limite,
                "concept" => $singleField->concepto,  
                //   Info Total
                "subtotal" => self::formatStringPrice($singleField->total),
                "discount" => self::formatStringPrice($singleField->valor_descuento),
                "netSubtotal" => self::formatStringPrice($singleField->subtotal),
                "iva" => $singleField->iva,
                "ivaInfo"=>self::formatRetention($singleField->iva, $singleField->porc_iva),
                "sourceRetention" => self::formatRetention($singleField->retencion_enlafuente, $singleField->porc_retencion_enlafuente),
                "reteiva" => self::formatRetention($singleField->retencion_iva, $singleField->porc_retencion_iva),
                "reteica" => self::formatRetention($singleField->retencion_ica, $singleField->porc_retencion_ica),
                "netTotal" => $singleField->total_neto,
                "identity" => $type,
                "checkoutInvoice"=>$prefix.$singleField->Id
            );    
        }
        
        return $response;
    }

    private static function formatRetention($type, $pcttype){
                
        $retention = !empty($type) ? self::formatStringPrice($type) : self::formatStringPrice(0);
        $pctretention = !empty($pcttype) && !empty($type) ? "(".$pcttype."%)" : "(0%)";
        return $retention." ".$pctretention;
        
    }

    private static function formatStringPrice($value){
        return "$ ".number_format($value, 2, '.',',')." COP";
    }
}
