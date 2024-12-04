<?php

namespace App\Http\Controllers;

use App\Common\RestrictiveListTypeValidation;
use App\Events\RestrictiveList\Process\ProcessRestrictiveListSaveLogEvent;
use App\Helpers\ClientRegister\HelperClientRegister;
use App\Http\Validation\Validate;
use App\Models\DetalleConfClientes;
use App\Models\GrantUser;
use App\Models\PreRegister;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;

class ApiExpressClientRegister extends HelperClientRegister
{
    const CLIENT_ID = 'clientId';
    const SUCCESS = 'success';
    const DIGIT = 'digit';
    const LAST_ACTION = 'lastAction';
    const STATUS = 'status';
    const CLIENT_ID_APIFY_PRIVATE = 4877;


    /**
     * @param array $data
     * @param bool $entityAlliedIsplus
     * @return PreRegister
     */
    private function setClientPreRegister(array $data, bool $entityAlliedIsplus, $isInRestrictiveList = false)
    {
        $dateNow = new DateTime('now');
        $clientPreregister = new PreRegister();
        $clientPreregister->cel_number = $data['mobilePhone'];
        $clientPreregister->country = $data['country'];
        $clientPreregister->doc_number = $data['docNumber'];
        $clientPreregister->doc_type = $data['docType'];
        $clientPreregister->email = $data['mail'];
        $clientPreregister->names = isset($data['firstNames']) ? $data['firstNames'] : '';
        $clientPreregister->surnames = isset($data['lastNames']) ? $data['lastNames'] : '';
        $clientPreregister->user_type = $data['userType'];
        $clientPreregister->created_at = $dateNow;
        $clientPreregister->restricted_user = false;
        $clientPreregister->digito = isset($data['digit']) ? $data['digit'] : null;
        $clientPreregister->nombre_empresa = isset($data['companyName']) ? $data['companyName'] : '';
        $clientPreregister->password_jwt = $data['password'];
        $clientPreregister->alianza_id = 1;
        $clientPreregister->plan_id = 1010;
        $clientPreregister->meta_tag = 'Apify - Registro express';
        $clientPreregister->restricted_user = $isInRestrictiveList;

        if ($entityAlliedIsplus) {
            $clientPreregister->id_cliente_entidad_aliada = $data['clientId'];
            //si el registro lo hace un plus, puede agregarle un id aliado al cliente que registra
            $clientPreregister->id_aliado = $data['referenceId'];
        } else {
            $clientPreregister->id_cliente_entidad_aliada = self::CLIENT_ID_APIFY_PRIVATE;
            //si el registro lo hace una entidad aliada estandar (se marca como un aliado)
            if (isset($data['referenceId'])) {
                $clientPreregister->id_aliado = $data['referenceId'];
            } else {
                $clientPreregister->id_aliado = $data['clientId'] !== self::CLIENT_ID_APIFY_PRIVATE ? $data['clientId'] : null;
            }
        }

        if ($data["gateway"]) {
            $clientPreregister->plan_id = 1011;
            $clientPreregister->proforma = $data['proforma'];
        }

        $strToke = $dateNow->format("Y-m-d H:i:s") . '' . $clientPreregister->doc_number . '' . $clientPreregister->doc_type;
        $token = md5($strToke);
        $clientPreregister->token = $token;

        $clientPreregister->request = json_encode($data);
        $clientPreregister->save();

        return $clientPreregister;
    }

    private function sendEmail(PreRegister &$preRegister)
    {
        $grantUser = GrantUser::where('cliente_id', $preRegister->cliente_id)->first();
        if (!$grantUser) {
            return false;
        }

        $date = (new DateTime('now'))->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');
        $token = md5((strtotime($date)) . (string)$preRegister->cliente_id);
        $grantUser->confirmation_token = $token;
        $grantUser->password_requested_at = $date;
        $grantUser->save();

        // envio de correo para clientes creados por epayco o entidad aliada estandar
        $url = sprintf(
            '%s/registro/activar/express/%s',
            getenv('DASHBOARD_URL'),
            $token

            );

        $params = [
            'url' => $url,
        ];
        $preRegister->url_validate = $params['url'];
        $preRegister->save();

        return $this->emailPanelRest(
            'Asignación de contraseña',
            $grantUser->username,
            'registro_express',
            $params
        );
    }

    /**
     * @param PreRegister $data
     */
    private function sendEmailEntityAllied(PreRegister $data)
    {
        $confEntityAllied = DetalleConfClientes::where('cliente_id', $data->id_cliente_entidad_aliada)
            ->where('config_id', 50)
            ->first();

        if (!$confEntityAllied) {
            return;
        }

        $conf = $confEntityAllied->valor;
        $arrayConf = json_decode($conf, true);

        if (
            (!isset($arrayConf['enviarEmail']))
            || !isset($arrayConf['urlResetPassword'])
            || !isset($arrayConf['plantillaEmail'])
        ) {
            return;
        }

        if ($arrayConf['enviarEmail'] !== true) {
            return;
        }

        $grantUser = GrantUser::where('cliente_id', $data->cliente_id)->first();
        $preRegister = PreRegister::where('cliente_id', $data->cliente_id)->first();
        if (!$grantUser || !$preRegister) {
            return;
        }

        //crear token para hacer reset de la password
        $date = (new DateTime('now'))->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');
        $token = md5((strtotime($date)) . (string)$data->cliente_id);
        $grantUser->confirmation_token = $token;
        $grantUser->password_requested_at = $date;
        $grantUser->save();


        $url = sprintf(
            '%s/%s',
            $arrayConf['urlResetPassword'],
            $token
        );
        $params = [
            'url' => $url,
            'user' => isset($data->nombre_empresa) ? $data->nombre_empresa : $data->names . $data->surnames,
            'type' => 'crear contraseña'
        ];
        $preRegister->url_validate = $params['url'];
        $preRegister->save();

        return $this->emailPanelRest(
            'Crear contraseña ePayco',
            $data->email,
            $arrayConf['plantillaEmail'],
            $params
        );
    }
}
