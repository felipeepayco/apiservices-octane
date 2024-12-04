<?php

namespace App\Helpers\Transaction;

use App\Events\LinkPaymentPse\Process\ProcessCreateLinkPaymentPseEvent;
use App\Helpers\Logs\LogApiservices;
use App\Models\Clientes;
use App\Models\RedirectPaymentMethod;
use Epayco\Utils\OpensslEncrypt;
use phpDocumentor\Reflection\Types\Integer;
use WpOrg\Requests\Requests;

class HelperTransaction
{

    const DATA_TRANSACTION = [
        "ref_payco" => "refEpayco",
        "factura" => "invoice",
        "descripcion" => "description",
        "valor" => "value",
        "iva" => "tax",
        "banco" => "bank",
        "baseiva" => "taxBase",
        "moneda" => "currency",
        "estado" => "state",
        "respuesta" => "stateMessage",
        "autorizacion" => "authorizationCode",
        "recibo" => "receipt",
        "fecha" => "dateTime",
        "franquicia" => "channel",
        "cod_respuesta" => "responseCode",
        "ip" => "ip",
        "enpruebas" => "test",
        "tipo_doc" => "docType",
        "documento" => "docNumber",
        "nombres" => "name",
        "apellidos" => "lastName",
        "email" => "email",
        "ciudad" => "city",
        "direccion" => "address",
        "ind_pais" => "country",
        "urlbanco" => "urlRedirect",
        "ico" => "icoTax"
    ];

    /** @var $urlResponse string */
    private $urlResponse;

    /** @var $urlLinkDaviplata string */
    private $urlLinkDaviplata = "https://daviplata.page.link";

    /** @var $urlDaviplata string */
    private $urlDaviplata = "https://seller.daviplata.com";

    /** @var $client Clientes */
    private $client;

    /** @var $params */
    private $params;

    /**
     * Función para consultar el cliente.
     * HelperTransaction constructor.
     * @param integer $id_cliente
     * @param array $fieldValidation
     */
    public function __construct($id_cliente, array $fieldValidation)
    {
        $this->client = Clientes::find($id_cliente);
        $this->params = $fieldValidation;
    }

    public function translateTransactionRest($transaction, $urlResponse = "")
    {
        $this->urlResponse = $urlResponse;
        $newTransaction = [];
        foreach ($transaction as $key => $trx) {
            if (isset(self::DATA_TRANSACTION[$key])) {
                $newTransaction[self::DATA_TRANSACTION[$key]] = $trx;
            }
        }

        //Agregar url de redirección para realizar el pago.
        if ($transaction->estado === "Pendiente") $this->urlRedirect($newTransaction);

        return $newTransaction;
    }

    private function urlRedirect(&$newTransaction)
    {
        $newTransaction["urlRedirect"] = "";

        //Validar si el medio de Pago contiene url para agregar urlRedirect
        $redirectPaymentMethod = RedirectPaymentMethod::where("id_payment_method", $newTransaction["channel"])->first();

        if ($redirectPaymentMethod) {
            $terminal = $this->createLinkPaymentPSE($newTransaction, $redirectPaymentMethod->url);
            //Validar si sera con la ref_payco o de la tabla donde creamos el link de pago PSE
            $newTransaction["urlRedirect"] = $redirectPaymentMethod->url . "?terminal=" . $terminal;
        }

        //Si el canal es DPA Daviplata App, se debe consumir firabase para crear la transacción.
        if ($newTransaction["channel"] === "DPA") {
            $newTransaction["urlRedirect"] = $this->createLinkFirebaseDaviplata($newTransaction["refEpayco"]);
            $this->createLinkPaymentPSE($newTransaction, $newTransaction["urlRedirect"]);
        }
    }

    /**
     * @param $refPayco
     * @return mixed
     * @throws \ErrorException
     */
    public function createLinkFirebaseDaviplata($refPayco)
    {
        try {

            $data = [
                "dynamicLinkInfo" => [
                    "domainUriPrefix" => getenv("DAVIPLATA_DOMAIN_URI_PREFIX"),
                    "link" => "{$this->urlDaviplata}?re={$refPayco}&dn={$this->client->celular}&ur={$this->urlResponse}",
                    "androidInfo" => [
                        "androidPackageName" => getenv("BUNDLE_ID_ANDROID_DP")
                    ],
                    "iosInfo" => [
                        "iosBundleId" => getenv("BUNDLE_ID_IOS_DP")
                    ]
                ]
            ];

            $baseUrlFirebase = getenv("BASE_URL_FIREBASE_LINK");
            $keyFirebase = getenv("KEY_FIREBASE");
            $url = "{$baseUrlFirebase}?key={$keyFirebase}";
            $headers = ['Accept' => 'application/json', "Content-Type" => "application/json"];
            $body = json_encode($data);
            $response = Requests::post($url, $headers, $body);

            LogApiservices::setGeneralLog([
                "action"=>"daviplata_social_seller_link",
                "externalRequest"=>json_encode($data),
                "url"=>$url,
                "details"=>json_encode($response)
            ]);

            if ($response->status_code >= 200) {
                $dataResponse = json_decode($response->body);
                if (isset($dataResponse->shortLink)) {
                    return $dataResponse->shortLink;
                }
            }
            throw new \ErrorException("Error create link Daviplata App.", 106);
        } catch (\Exception $e) {
            LogApiservices::setGeneralLog([
                "action"=>"daviplata_social_seller_link",
                "mensaje"=>$e->getMessage(),
                "details"=>$e->getMessage(),
                "url"=>$url
            ]);
            throw new \ErrorException($e->getMessage(), $e->getCode());
        }
    }

    private function encrypt($data, $key, $encrypt = "")
    {
        openssl_public_encrypt(json_encode($data), $encrypt, $key);

        return base64_encode($encrypt);

    }

    /**
     * @param $newTransaction
     * @param $urlRedirect
     * @return mixed
     * @throws \ErrorException
     */
    private function createLinkPaymentPSE($newTransaction, $urlRedirect)
    {
        try {
            $arrParameters = [
                "invoiceId" => $newTransaction["refEpayco"],
                "clientId" => $this->client->Id,
                "urlPayment" => $urlRedirect,
                "urlResponse" => $this->urlResponse,
                "urlConfirmation" => $this->params["urlConfirmation"],
                "methodConfirmation" => $this->params["methodConfirmation"],
                "totalPayment" => $newTransaction["value"],
                "product" => $newTransaction["description"],
                "emailPayment" => $newTransaction["email"],
                "docType" => $newTransaction["docType"],
                "docNumber" => $newTransaction["docNumber"],
                "name" => $newTransaction["name"],
                "lastName" => $newTransaction["lastName"],
                "cellPhone" => $this->params["cellPhone"],
                "phone" => $this->client["celular"],
            ];

            $terminal = event(new ProcessCreateLinkPaymentPseEvent($arrParameters));

            if (!isset($terminal[0]["data"]["id"])) {
                throw new \ErrorException("Error create link payment pse.", 106);
            }

            return $terminal[0]["data"]["id"];
        } catch (\Exception $e) {
            throw new \ErrorException($e->getMessage(), $e->getCode());
        }
    }

}
