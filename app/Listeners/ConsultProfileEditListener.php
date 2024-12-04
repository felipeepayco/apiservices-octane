<?php
namespace App\Listeners;

use App\Events\ConsultProfileEditEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\BblClientes;
use DB;
use Illuminate\Http\Request;

class ConsultProfileEditListener extends HelperPago
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * Handle the event.
     * @return void
     */
    public function handle(ConsultProfileEditEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $grantUserId = $fieldValidation["grantUserId"];

            $profile = BblClientes::where("id", $clientId)
                ->select("id", "nombre as firstName", "apellido as lastName", "razon_social as companyName", "telefono as cellPhone", "email as correo")
                ->first();
            if ($profile["logo"] != null) {
                $profile["logo"] = getenv('RACKSPACE_CONTAINER_BASE_PUBLIC_URL') . '/' . 'logos_clientes/' . $profile["logo"];
            }

            $profile["logoAlly"] = "";
            $profile["domain"] = "";

            $this->addMultiClientData($profile, $grantUserId);

            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $profile;

        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error inesperado al consultar  perfil con los parametros datos";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalerrores' => $validate->totalerrors, 'errores' =>
                $validate->errorMessage);
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;
        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }

    private function addMultiClientData(&$profile, $grantUserId)
    {

        $multi_accounts = BblClientes::where("id", $profile["id"])
            ->select("id as clientId",
                DB::raw("CONCAT(nombre, ' ', apellido) AS username"),
                "nombre as firstName", "apellido as lastName", "razon_social as companyName", "telefono as cellPhone", "email as correo")
            ->get();

        $multi_accounts[0]["clientStatus"] = 1;
        $multi_accounts[0]["logo"] = "1_1431448396.png";

        

        $profile["email"] = $profile["correo"];
        $profile["emailVerify"] = $profile["correo"];
        $profile["grantUserName"] = $profile["nombre"] . " " . $profile["apellido"];
        $profile["grantUserId"] = env('CLIENT_ID_BABILONIA');
        $profile["roles"] = ["ROLE_ADMIN"];
        $profile["phone2fa"] = "";
        $profile["phone2faCode"] = "";
        $profile["email2fa"] = "";
        $profile["email2faCode"] = "";
        $profile["countryId"] = 1;
        $profile["countryCode"] = "CO";
        $profile["multiClients"] = $multi_accounts;
    }

    private function getLogoAlly($saldoAliados)
    {
        $logo = "";

        if (!is_null($saldoAliados) && !is_null($saldoAliados["logoAliado"]) && $saldoAliados["logoAliado"] !== "") {
            $logo = getenv('AWS_BASE_PUBLIC_URL') . '/' . $saldoAliados["logoAliado"];
        }

        return $logo;
    }
}
