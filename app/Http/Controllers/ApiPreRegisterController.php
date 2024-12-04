<?php namespace App\Http\Controllers;


use App\Helpers\ClientRegister\HelperClientRegister;
use App\Helpers\Logs\LogApiservices;
use App\Helpers\Messages\CommonText;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate;
use App\Models\Clientes;
use App\Models\DetalleConfClientes;
use App\Models\GrantUser;
use App\Models\PreRegister;
use App\Models\ProductosClientes;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use App\Helpers\Pago\HelperPago;
use App\Models\ConfPais;
use App\Exceptions\GeneralException;
use App\Models\TipoDocumentos;

class ApiPreRegisterController extends HelperPago
{

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;
    const ID_COLOMBIA = 1;
    const COD_COLOMBIA = "CO";


    /**
     * ApiPreRegisterController constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function isDefaultCountry($country){
        $confCountryExist = ConfPais::where("cod_pais",$country)->first();
        return (is_null($confCountryExist) || $country ==  CommonText::COUNTRY_CODE_CO);
    }


    /**
     * @param int $alliedEntity
     * @param array $data
     * @param int $clientId
     */
    private function sendEmailGateway(int $alliedEntity, array $data, int $clientId)
    {
        $confEntityAllied = DetalleConfClientes::where('cliente_id', $alliedEntity)
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

        $grantUser = GrantUser::where('cliente_id', $clientId)->first();
        $preRegister = PreRegister::where('cliente_id', $clientId)->first();
        if (!$grantUser || !$preRegister) {
            return;
        }

        //crear token para hacer reset de la password
        $date = (new DateTime('now'))->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');
        $token = md5((strtotime($date)) . (string)$clientId);
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
            'user' => isset($data['companyName']) ? $data['companyName'] : $data['firstNames'] . $data['lastNames'],
            'type' => 'crear contraseña'
        ];
        $preRegister->url_validate = $params['url'];
        $preRegister->save();

        $this->emailPanelRest(
            'Crear Contraseña EPAYCO',
            $data['mail'],
            $arrayConf['plantillaEmail'],
            $params
        );
    }

    private function duplicateAccount($duplicateId, &$arr_parametros){
        
        try {
            
            
            $duplicate = Clientes::where('id', $duplicateId)->first();
            $codDoc = TipoDocumentos::select('codigo')->where('id', $duplicate->tipo_doc)->first();
        
            $arr_parametros["userType"] = $duplicate->tipo_cliente === "C" ? "comercio" : "persona" ;
            $arr_parametros["firstNames"] = $duplicate->nombre;
            $arr_parametros["lastNames"] = $duplicate->apellido ? $duplicate->apellido : "apellido";
            $arr_parametros["companyName"] = $duplicate->nombre_empresa;
            $arr_parametros["docType"] = $codDoc->codigo;
            $arr_parametros["docNumber"] = $duplicate->documento;
            $arr_parametros["digito"] = $duplicate->digito;
            $arr_parametros["country"] = $duplicate->id_pais;            
            $arr_parametros["prefijo"] = (int)$duplicate->ind_pais;
            $arr_parametros["mobilePhone"] = $duplicate->celular;
            $arr_parametros["mail"] = $duplicate->email;
            $arr_parametros["password"] = $duplicate->constrasena;
            $arr_parametros["web"] = true;

            $confCountry = DetalleConfClientes::where("cliente_id",$duplicateId)
                ->where("config_id",CommonText::CONF_CLIENTES_COUNTRY_CONFIG_ID)
                ->first();
            $arr_parametros["confCountry"] = $confCountry->valor;
            return $arr_parametros;


        } catch (GeneralException $error) {
            return $error->getMessage();
        }
    }

    private function validateMultiAccount(&$arr_parameters){

        $validateMultiAccount = [
            "isMultiAccountRequest"=>isset($arr_parameters["multiAccount"])
        ];

        if($validateMultiAccount["isMultiAccountRequest"]){
            $validateMultiAccount["duplicate"] = CommonValidation::validateIsSet($arr_parameters["multiAccount"],"duplicate",false);
            $validateMultiAccount["duplicateClientId"] = CommonValidation::validateIsSet($arr_parameters["multiAccount"],"duplicateClientId",null);
            $validateMultiAccount["grantUserId"] = CommonValidation::validateIsSet($arr_parameters["multiAccount"],"grantUserId",null);

            $grantUser = GrantUser::where("id",$validateMultiAccount["grantUserId"])->first();
            if(!is_null($grantUser)){
                $arr_parameters["mail"] = $grantUser->email;
            }
        }

        return $validateMultiAccount;
    }
}
