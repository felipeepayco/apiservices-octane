<?php

namespace App\Helpers\Pago;

use App\Helpers\Pago\HelperPago as HelperPago;
use App\Models\Clientes as Clientes;
use App\Events\ValidacionComercioEvent;
use App\Models\LLavesClientes as LLavesClientes;
use App\Http\Lib\DescryptObject as DescryptObject;
use Illuminate\Http\Request as Request;

class ValidarComercio extends HelperPago {
    public function validar($publickey) {
        $LLavesclientes = LLavesClientes::where('public_key', $publickey)
                ->first();

        if (is_object($LLavesclientes)) {
            $error = $this->getErrorCheckout('E000');

            $this->LLavesclientes = $LLavesclientes;
            
            $this->private_key = $this->LLavesclientes->private_key_decrypt;

            return array(
                'success' => true,
                'title_response' => 'Cliente Validado Exitosamente',
                'text_response' => 'Ok',
                'last_action' => 'validarcliente',
                'data' => array('codError' => $error->error_code,
                    'errorMessage' => $error->error_message)
            );
        } else {
            $validate = new Validate();
            $error = $this->getErrorCheckout('C001');
            $validate->setError($error->getErrorCode(), $error->error_message);

            return array(
                'success' => false,
                'title_response' => 'Cliente Invalido',
                'text_response' => 'No se pudo validar el cliente',
                'last_action' => 'validarcliente',
                'data' => array('totalerrores' => $validate->totalerrors,
                    'errores' => $validate->error_message),
            );
        }
        
    }

}