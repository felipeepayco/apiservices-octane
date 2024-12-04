<?php

namespace App\Http\Lib;



use App\Models\PayPalConfig;
use App\Models\PaypalRetiros;
use App\Models\PayPalToken;

class PaypalService
{


    public function __construct()
    {
        $paypalConfigWithdraws = PayPalConfig::where('descripcion', 'withdraws')->first();
        $paypalConfigPayments = PayPalConfig::where('descripcion', 'payments')->first();
        $this->paypalConfigWithdraws = $paypalConfigWithdraws;
        $this->paypalConfigPayments = $paypalConfigPayments;
    }

    public function getInitialToken($app)
    {
        $dateNow = new \DateTime("now");
        $arPayPalToken = PayPalToken::orderBy('id', 'DESC')->where('tipo', $app)->first();
        if ($arPayPalToken && ($dateNow < $arPayPalToken->vencimiento)) {
            return $arPayPalToken->response;
        }

        $curl = curl_init();
        if ($app == 'withdraws') {
            $user = $this->paypalConfigWithdraws->client_id;
            $pass = $this->paypalConfigWithdraws->secret;
            $baseUrl = $this->paypalConfigWithdraws->api_base_url;
        } else {
            $user = $this->paypalConfigPayments->client_id;
            $pass = $this->paypalConfigPayments->secret;
            $baseUrl = $this->paypalConfigPayments->api_base_url;
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$baseUrl}/v1/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=client_credentials&content_type=application%2Fx-www-form-urlencoded",
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),

        ));
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$user:$pass");

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {

            $payPalToken = json_decode($response);
            $dateExpire = $dateNow->add(new \DateInterval("PT{$payPalToken->expires_in}S"));

            $arPayPalToken = new PayPalToken();
            $arPayPalToken->response = ($response);
            $arPayPalToken->vencimiento = ($dateExpire);
            $arPayPalToken->tipo = ($app);
            $arPayPalToken->token = ($payPalToken->access_token);
            $arPayPalToken->save();

            return $response;
        }
    }

    public function getUserToken($lastCode, $initialTokenBearer)
    {
        $curl = curl_init();


        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->paypalConfigWithdraws->api_base_url}/v1/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=authorization_code&content_type=application%2Fx-www-form-urlencoded&code={$lastCode}",
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Content-Type: application/json",
                "authorization: Bearer " . $initialTokenBearer,
                //"Postman-Token: b711e377-d8e8-431a-ae59-8eb01f6310f7",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function getRefeshToken($initialTokenBearer, $refreshToken)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->paypalConfigWithdraws->api_base_url}/v1/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=refresh_token&content_type=application%2Fx-www-form-urlencoded&refresh_token={$refreshToken}",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Authorization: Bearer {$initialTokenBearer}",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Length: 237",
                "Content-Type: application/x-www-form-urlencoded",
                "Cookie: X-PP-SILOVER=name%3DSANDBOX3.API.1%26silo_version%3D1880%26app%3Dapiplatformproxyserv%26TIME%3D1296598365%26HTTP_X_PP_AZ_LOCATOR%3Dsandbox.slc",
                "Host: api.sandbox.paypal.com",
                "Postman-Token: 9349a9c8-f033-40da-a584-8667797c2c69,a7fbb331-dbab-4485-8ee1-5990861c1cb0",
                "User-Agent: PostmanRuntime/7.17.1",
                "cache-control: no-cache"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function getUserInfo($token)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            //CURLOPT_URL => "{$this->paypalConfigWithdraws->getApiBaseUrl()}/v1/oauth2/token/userinfo?schema=openid",
            CURLOPT_URL => "{$this->paypalConfigWithdraws->api_base_url}/v1/identity/oauth2/userinfo?schema=paypalv1.1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$token}",
                //"Postman-Token: 099d5bbe-4804-460c-b186-14a1a7e2c536",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function getUserBalance($token)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->paypalConfigWithdraws->api_base_url}/v1/wallet/balance-accounts",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$token}",
                //"Postman-Token: 099d5bbe-4804-460c-b186-14a1a7e2c536",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function getWithdrawInfo($idWithdraw, $clientPaypal)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->paypalConfigWithdraws->api_base_url}/v2/transfers/withdrawals/{$idWithdraw}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$clientPaypal->customer_access_token}",
                "Content-Type: application/json",
                "PayPal-Request-Id: {$idWithdraw}",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function setWithDraw($value, $clientPaypal, $id)
    {

        $curl = curl_init();
        $postFields = "{\"amount\": {\"currency_code\": \"{$clientPaypal->moneda_balance}\",\"value\": \"{$value}\"},\"destination\": {\"type\": \"ACCOUNT_NUMBER\",\"value\": \"{$this->paypalConfigWithdraws->parthner_receiver}\"},\"method\": \"BANK_MANAGED_WITHDRAWAL\"}";

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->paypalConfigWithdraws->api_base_url}/v2/transfers/withdrawals",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer {$clientPaypal->customer_access_token}",
                "Content-Type: application/json",
                "PayPal-Request-Id: ID{$id}",
                //"Postman-Token: 0e7f9b74-3ccc-4b76-bf40-d730157458af",
                "cache-control: no-cache"
            ),
        ));

        /** @var $arPaypalRetiro PaypalRetiros */
        $arPaypalRetiro = PaypalRetiros::where('code', $id)->first();
        $arPaypalRetiro->request_retiro = ($postFields);
        $arPaypalRetiro->save();

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function getUrlLoginPayment($token, $clienteId, $urlReturn)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->paypalConfigPayments->api_base_url}/v1/customer/partner-referrals",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{
                                    "customer_data": {
                                      "partner_specific_identifiers": [{
                                        "type": "TRACKING_ID",
                                        "value": "' . $clienteId . '"
                                      }]
                                    },
                                    "requested_capabilities": [{
                                      "capability": "API_INTEGRATION",
                                      "api_integration_preference": {
                                        "partner_id": "' . $this->paypalConfigPayments->parthner_reciever . '",
                                        "rest_api_integration": {
                                          "integration_method": "PAYPAL",
                                          "integration_type": "THIRD_PARTY"
                                        },
                                        "rest_third_party_details": {
                                          "partner_client_id": "' . $this->paypalConfigPayments->client_id . '",
                                          "feature_list": [
                                            "PAYMENT",
                                            "REFUND"
                                            
                                          ]
                                        }
                                      }
                                    }],
                                    "web_experience_preference": {
                                      "partner_logo_url": "https://example.com/paypal.jpg",
                                      "return_url": "' . $urlReturn . '",
                                      "action_renewal_url": "https://example.com/renew-prefill-url"
                                    },
                                    "collected_consents": [{
                                      "type": "SHARE_DATA_CONSENT",
                                      "granted": true
                                    }],
                                    "products": [
                                      "EXPRESS_CHECKOUT"
                                    ]
                                  }',
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Content-Type: application/json",
                "authorization: Bearer " . $token,
                //"Postman-Token: b711e377-d8e8-431a-ae59-8eb01f6310f7",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }

    }


}
