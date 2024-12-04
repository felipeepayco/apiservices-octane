<?php

namespace App\Listeners\Services;

use App\Common\TaxCodes;
use App\Helpers\Messages\CommonText;
use App\Helpers\Pago\HelperPago;
use App\Models\ConfTaxes;
use App\Models\DetalleConfClientes;
use App\Models\Municipios;
use App\Models\RecaudoProyecto;
use App\Models\ResponsabilidadFiscal;
use App\Models\ResponsabilidadFiscalClientes;

class BillService extends HelperPago {

    public function __construct()
    {
        // comment
    }

    public function generateUrl($projectId, $productClientId)
    {
        $detalleConfCliente = DetalleConfClientes::select("detalle_conf_clientes.*")
        ->leftJoin('conf_clientes', 'conf_clientes.id', 'detalle_conf_clientes.config_id')
        ->where("detalle_conf_clientes.cliente_id", getenv("CLIENT_ID_APIFY_PRIVATE"))
        ->where("conf_clientes.nombre_var", "dominio_recaudo")->first();

        $recaudoProyecto = RecaudoProyecto::where("id", $projectId)->first();

        return $detalleConfCliente["valor"] . $recaudoProyecto["url"] . "?option1=3&value1=" . $productClientId . "&checkout=true";
    }

    public function calculateTaxAndRetentions($client,$productValue){

        $reteiva = 0;
        $retefuente =0;
        $iva = 0;

        $skipTaxes = DetalleConfClientes::where("cliente_id",$client->id)
            ->where("config_id",1)
            ->first();

        if(is_null($skipTaxes)){
            $clientIva = $this->getClientIVA($client);
            $responsabilities = $this->getClientResponsabilities($client);
            $iva = round($productValue*($clientIva->valor/100), 2);

            foreach ($responsabilities as $responsability){
                if(in_array($responsability["codigo"],[TaxCodes::RETEFUENTE_25_CODE,TaxCodes::RETEFUENTE_35_CODE])){
                    $retefuente = round(($responsability["valor"]/100)*$productValue, 2);
                }

                if(in_array($responsability["codigo"],[TaxCodes::RETEIVA_CODE])){
                    $reteiva = round(($responsability["valor"]/100)*$iva, 2);
                }
            }
        }

        return [
            "reteiva"=>$reteiva,
            "retefuente"=>$retefuente,
            "iva"=>$iva
        ];

    }

    public function getClientResponsabilities($client){
        return ResponsabilidadFiscalClientes::select("conf_taxes.valor","conf_taxes.codigo")
            ->join("responsabilidad_fiscal","responsabilidad_fiscal_clientes.id_responsabilidad_fiscal","responsabilidad_fiscal.id")
            ->join("responsabilidad_fiscal_conf_taxes","responsabilidad_fiscal_conf_taxes.id_responsabilidad_fiscal","responsabilidad_fiscal.id")
            ->join("conf_taxes","conf_taxes.id","responsabilidad_fiscal_conf_taxes.id_conf_tax")
            ->where("id_cliente",$client->Id)->get()->toArray();
    }

    public function getClientIVA($client){
        return ConfTaxes::where("codigo",CommonText::IVA)
            ->where("pais",$client->id_pais)
            ->first();
    }

}