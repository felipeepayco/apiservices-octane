<?php


namespace App\Listeners\Services;

use App\Models\Clientes;
use App\Models\ClientesReconocimientoPublico;
use App\Models\ClientesRedesSociales;
use App\Models\ClientesSocios;
use App\Models\ContactosClientes;
use App\Models\CuentasBancarias;
use App\Models\DetalleClientes;
use App\Models\DetalleConfClientes;
use App\Models\DocumentosLegales;
use App\Models\LimClientesValidacion;
use App\Models\LimEmailSms;
use App\Models\ProductosClientes;
use App\Models\ResponsabilidadFiscalClientes;
use App\Exceptions\GeneralException;

class MultiAccountService
{
    public function duplicateAccount($clientId, $duplicateClientId){
        
        try {
            
            
            $originClient = Clientes::find($duplicateClientId);            
            $targetClient = Clientes::find($clientId);
            $targetClient->ind_ciudad = $originClient->ind_ciudad;
            $targetClient->ind_pais = $originClient->ind_pais;
            $targetClient->id_categoria = $originClient->id_categoria;
            $targetClient->id_subcategoria = $originClient->id_subcategoria;
            $targetClient->servicio = $originClient->servicio;
            $targetClient->promedio_ventas = $originClient->promedio_ventas;
            $targetClient->tipo_nacionalidad_clientes = $originClient->tipo_nacionalidad_clientes;
            $targetClient->pagweb = $originClient->pagweb;
            $targetClient->ciiu = $originClient->ciiu;
            $targetClient->id_tercero_siigo = $originClient->id_tercero_siigo;
            $targetClient->id_contacto_siigo = $originClient->id_contacto_siigo;
            $targetClient->responsable_iva = $originClient->responsable_iva;            
            
            
            // paso 3 //
            $targetClient->id_pais = $originClient->id_pais;
            $targetClient->id_region = $originClient->id_region;
            $targetClient->id_ciudad = $originClient->id_ciudad;
            $targetClient->direccion = $originClient->direccion;
            $targetClient->dir_tipo_nomenclatura = $originClient->dir_tipo_nomenclatura;
            $targetClient->dir_numero_nomenclatura = $originClient->dir_numero_nomenclatura;
            $targetClient->dir_numero_puerta1 = $originClient->dir_numero_puerta1;
            $targetClient->dir_numero_puerta2 = $originClient->dir_numero_puerta2;
            $targetClient->dir_tipo_propiedad = $originClient->dir_tipo_propiedad;
            $targetClient->dir_detalle_tipo_propiedad = $originClient->dir_detalle_tipo_propiedad;
            $targetClient->dir_descripcion = $originClient->dir_descripcion;
            
            $targetClient->nombre_empresa = $originClient->nombre_empresa;
            $targetClient->razon_social = $originClient->razon_social;
            $targetClient->nombre = $originClient->nombre;
            $targetClient->apellido = $originClient->apellido;
            $targetClient->aliado = $originClient->aliado;
            $targetClient->fase_integracion = $originClient->fase_integracion;
            $targetClient->telefono = $originClient->telefono;
            $targetClient->celular = $originClient->celular;
            $targetClient->documento = $originClient->documento;

            
            $targetClient->save();

            
            //
            
            $originClientLimit = LimClientesValidacion::where('cliente_id', $duplicateClientId)->get();
            LimClientesValidacion::where('cliente_id', $clientId)->delete();            
            $this->setClientLimit($clientId, $originClientLimit);

            //
            
            $originClientContact = ContactosClientes::where('id_cliente', $duplicateClientId)
            ->where('tipo_contacto', 'legal')
            ->first();
        
            ContactosClientes::where('id_cliente', $clientId)
            ->where('tipo_contacto', 'legal')
            ->delete();
            
            if($originClientContact){
                $newClientContact = $originClientContact->replicate();
                $newClientContact->id_cliente = $clientId;
                $newClientContact->save();
            }
                        
            //
            
            $originLimEmailSms = LimEmailSms::where('cliente_id', $duplicateClientId)->first();            
            LimEmailSms::where('cliente_id', $clientId)->delete();
            
            if($originLimEmailSms){
                $newLimEmailSms = $originLimEmailSms->replicate();
                $newLimEmailSms->cliente_id = $clientId;
                $newLimEmailSms->save();
            }

            //
        
            $originSocialClient = ClientesSocios::where('cliente_id', $duplicateClientId)->get();            
            $this->setSocialClient($clientId, $originSocialClient);
            
            // Social Networks

            $originSocialNetworks = ClientesRedesSociales::where('cliente_id', $duplicateClientId)->get();            
            $this->setSocialNetworks($clientId, $originSocialNetworks);


            $originClientPublicRecon = ClientesReconocimientoPublico::where('id_cliente', $duplicateClientId)->first();            
            ClientesReconocimientoPublico::where('id_cliente', $clientId)->delete();
            
            if($originClientPublicRecon){
                $newClientPublicRecon = $originClientPublicRecon->replicate();
                $newClientPublicRecon->id_cliente = $clientId;
                $newClientPublicRecon->save();
            }
                                    
            //
                        
            $originClientFiscalRespon = ResponsabilidadFiscalClientes::where('id_cliente', $duplicateClientId)->get();                        
            $this->setClientFiscalRespon ($clientId, $originClientFiscalRespon);
            //

            $originClientConfEmail = DetalleConfClientes::where('cliente_id', $duplicateClientId)
            ->where('config_id', 3)
            ->first();
            
            DetalleConfClientes::where('cliente_id', $clientId)
            ->where('config_id', 3)
            ->delete();


            if($originClientConfEmail){
                $newClientConfEmail = $originClientConfEmail->replicate();
                $newClientConfEmail->cliente_id = $clientId;
                $newClientConfEmail->save();
            }
            
            // Clonacion de documentos legales sin bancaria id

            DocumentosLegales::where('cliente_id', $clientId) 
            ->delete();

            $originLegalDoc = DocumentosLegales::where('cliente_id', $duplicateClientId) 
            ->where('bancaria_id', null)      
            ->get();
            
            $this->setLegalDoc($clientId, $originLegalDoc);
            
            //

            $originBankAccount = CuentasBancarias::where('cliente_id', $duplicateClientId)->get();            
            $this->setBankAccounts($clientId, $originBankAccount);
        
        } catch (GeneralException $error) {
            return $error->getMessage();
        }
    }

    
    private function setClientLimit ($clientId, $originClientLimit){
        
        if($originClientLimit){
            foreach($originClientLimit as $stepsOrigin){

                $newStep = $stepsOrigin->replicate();
                $newStep->cliente_id = $clientId;
                $newStep->save();
                
            }
        }
    }

    private function setSocialClient ($clientId, $originSocialClient){
        if($originSocialClient){
            foreach($originSocialClient as $social){

                $newSocialClient = $social->replicate();
                $newSocialClient->cliente_id = $clientId;
                $newSocialClient->save();
            
            }
        }
    }
    
    
    private function setSocialNetworks($clientId, $originSocialNetworks){
        if($originSocialNetworks){
            foreach($originSocialNetworks as $socialNetwork){

            $newSocialNetworks = $socialNetwork->replicate();
            $newSocialNetworks->cliente_id = $clientId;
            $newSocialNetworks->save();
            
            }
        }
    }

    private function setClientFiscalRespon($clientId, $originClientFiscalRespon){
        if($originClientFiscalRespon){
            foreach($originClientFiscalRespon as $fiscalRespon){

                $newClientFiscalRespon = $fiscalRespon->replicate();
                $newClientFiscalRespon->id_cliente = $clientId;
                $newClientFiscalRespon->save();
                
            }
        }
    }

    private function setLegalDoc($clientId, $originLegalDoc){
        if($originLegalDoc){
            foreach($originLegalDoc as $legalDoc){
                $newLegalDoc = $legalDoc->replicate();
                $newLegalDoc->cliente_id = $clientId;
                $newLegalDoc->save();
            }
        }
    }

    private function setBankAccounts($clientId, $originBankAccount){
        if($originBankAccount){
            foreach($originBankAccount as $bankAccount){

                $newBankAccount = $bankAccount->replicate();
                $newBankAccount->cliente_id = $clientId;
                $newBankAccount->save();
            
                // Clonacion de documentos legales con bancaria id                
                $originLegalDocsBank = DocumentosLegales::where('bancaria_id', $bankAccount->id)->get();

                if ($originLegalDocsBank !== null){
                    foreach($originLegalDocsBank as $legalDocBank){
                        $newLegalDocBank = $legalDocBank->replicate();
                        $newLegalDocBank->cliente_id = $clientId;
                        $newLegalDocBank->bancaria_id = $newBankAccount->id;
                        $newLegalDocBank->save();
                    }
                }

            }
        }
    }

    public function isEntityAllied($clientId){

        $entityAlliedIsplus = false;

        $productClient = ProductosClientes::where('cliente_id', $clientId)->where('estado', 1)
            ->whereHas('product', function ($query) {
                $query->where('tipo_plan', 15);
            })->first();

        if ($productClient) {
            $configProduct = json_decode($productClient->product->configuracion, true);
            $entityAlliedIsplus = $configProduct[0]['value'] === 'Plus' ? true : false;
        }

        return $entityAlliedIsplus;

    }
}