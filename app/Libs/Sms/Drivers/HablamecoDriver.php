<?php

namespace App\Libs\Sms\Drivers;

use WpOrg\Requests\Requests;

/**
 * Hablame Co SMS Documents
 * https://developer.hablame.co/smsdoc.php
 */
class HablamecoDriver extends Driver
{

    /**
     * Domains variable
     *
     * @var array
     */
    protected $domain = [
        'primary' => 'https://api101.hablame.co',
        'secondary' => 'https://api102.hablame.co',
    ];

    /**
     * path variable
     *
     * @var string
     */
    protected $path = '/api/sms/v2.1/send/';

    /**
     * Options variable
     *
     * @var array
     */
    protected $options;


    /**
     * HablameCo construct function
     *
     * @param string $account
     * @param string $apikey
     * @param string $token
     */
    public function __construct(string $account, string $apiKey, string $token)
    {
        $this->options = [
            'account' => $account,
            'apiKey' => $apiKey,
            'token' => $token,
            'flash' => 0,
            'sc' => null,
            'isPriority' => 0,
            'sendDate'=> time(),
            'request_dlvr_rcpt' => 0,
        ];
    }

    /**
     * Set Options function
     *
     * @return self
     */
    public function options($options = [])
    {
        $this->options = array_merge(
            $this->options,
            $options
        );

        return $this;
    }

    /**
     * Send Message function
     *
     * @return array
     */
    public function send()
    {
        $headers = [];

        $this->getValidations();

        $this->options['sms'] = $this->message;
        $this->options['toNumber'] = $this->recipient;

        try {
            $response = Requests::post(
                $this->getEndPoint(),
                $headers,
                $this->options,
                ["timeout" => 20]
            );
    
            $response = json_decode($response->body);
            return response()->json([
                'success' => $response->status === "1x000" ? true : false,
                'titleResponse' => 'Envio SMS HablameCo',
                'textResponse' => $response->error_description,
                'lastAction' => 'client_send_message',
                'data' => [
                    'status' => $response->status,
                    'reason' => $this->getErrorMessage($response->status),
                    'smsId' => $response->smsId ?? null,
                    'loteld' => $response->loteId ?? null,
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'titleResponse' => 'Error Envio SMS HablameCo',
                'textResponse' => 'Ha ocurrido un error enviado el SMS',
                'lastAction' => 'client_send_message',
                'data' => [
                    'provider' => 'ClientHablemeCo',
                    'message' => $ex->getMessage(),
                    'trace' => $ex->getTraceAsString(),
                ]
            ]);
        }
    }

    /**
     * Get EndPoint function
     *
     * @return string
     */
    protected function getEndPoint($endpoint = 'primary')
    {
        return $this->domain[$endpoint] . $this->path;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function getValidations()
    {
        if (array_key_exists('account', $this->options) && empty($this->options['account'])) {
            throw new \Exception('El parámetro [account] es obligatorio.');
        }

        if (array_key_exists('apiKey', $this->options) && empty($this->options['apiKey'])) {
            throw new \Exception('El parámetro [apiKey] es obligatorio.');
        }
        
        if (array_key_exists('token', $this->options) && empty($this->options['token'])) {
            throw new \Exception('El parámetro [token] es obligatorio.');
        }
    }

    /**
     * Get Error Message function
     *
     * @param string $key
     * @return string
     */
    protected function getErrorMessage($key)
    {
        $response = [
            '1x000' => 'SMS recíbido por hablame exitosamente',
            '1x001' => 'Se genera cuando el número de cuenta no esta definido',
            '1x002' => 'Se genera cuando el apiKey no se encuentra definido',
            '1x003' => 'Token no esta definido',
            '1x004' => 'Credenciales invalidas, verifique los campos de [account], [apiKey] y [token]',
            '1x005' => 'IP de origen vacia',
            '1x006' => 'Fecha de envío no valida',
            '1x007' => 'Fecha de envío supera los 2 meses',
            '1x009' => 'No hay mensajes para enviar, asegúrese de envíar los campos [toNumber] y [sms]',
            '1x010' => 'Demasiados numeros en el campo toNumber',
            '1x011' => 'Cuenta presenta bloqueo general',
            '1x012' => 'Cuenta presenta bloqueo por fraude',
            '1x013' => 'Cuenta presenta bloqueo por cartera',
            '1x014' => 'Cliente no tiene habilitado modulo de SMS',
            '1x015' => 'Cliente no tiene habilitado el uso del API',
            '1x016' => 'Error interno - no existe cuenta en dbr',
            '1x017' => 'Un sms prioritario no puede tener mas de un numero de destino',
            '1x018' => 'Ha superado el limite de envíos por segundo para un sms prioritario',
            '1x019' => 'IP no habilitada para enviar SMS',
            '1x020' => 'No tiene saldo disponible para realizar el envío',
            '1x021' => 'sms prioritario no puede ser programado',
        ];

        return $response[$key];
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function response($res)
    {
        $res = json_decode((string) $res, true);
        
        return array_merge($res, [
            'provider' => 'hablameco',
        ]);
    }
}
