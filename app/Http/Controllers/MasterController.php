<?php

namespace App\Http\Controllers;

use App\Http\Lib\Utils;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Models\Bancos;
use App\Models\Clientes;
use App\Models\CodigoCiiu;
use App\Models\DominioConfiguracionProyecto;
use App\Models\LogRest;
use App\Models\ResponsabilidadFiscal;
use App\Models\TckDepartamentos;
use App\Models\TckPrioridad;
use App\Models\TipoDocumentos;
use App\Models\ValidacionCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Pago\HelperPago;


class MasterController extends HelperPago
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new controller instance.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getDepartments(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";
        $indicative = isset($filter->indicative) ? $filter->indicative : "";
        $country = isset($filter->country) ? $filter->country : "";

        $listdepartamentos = DB::table("departamentos");
        if ($id) {
            $listdepartamentos->where('id', $id);
        }
        if ($name) {
            $listdepartamentos->where('nombre', $name);
        }
        if ($indicative) {
            $listdepartamentos->where('indicativo', $indicative);
        }
        if ($country) {
            $listdepartamentos->where('codigo_pais', $country);
        }
        $listdepartamentos = $listdepartamentos->get();

        $departamentos = array();

        foreach ($listdepartamentos as $row) {
            $departamentos[] = array("id" => $row->id, 'name' => $row->nombre, 'indicative' => $row->indicativo);
        }

        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "department list success",
            'lastAction' => "Query department",
            'data' => $departamentos,
        );

    }

    public function getCities(Request $request)
    {

        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $country = isset($filter->country) ? $filter->country : "";
        $department = isset($filter->department) ? $filter->department : "";
        $name = isset($filter->name) ? $filter->name : "";
        $indicative = isset($filter->indicative) ? $filter->indicative : "";

        $listciudades = DB::table("municipios")
            ->leftJoin("departamentos", "departamentos.id", "=", "municipios.id_departamento")
            ->leftJoin("paises", "paises.codigo_pais", "=", "departamentos.codigo_pais")
            ->addSelect("municipios.id as id")
            ->addSelect("municipios.nombre as nombre")
            ->addSelect("departamentos.nombre as nombre_departamento")
            ->addSelect("municipios.id_departamento as id_departamento")
            ->addSelect("municipios.indicativo as indicativo")
            ->addSelect("municipios.cod_dian as cod_dian")
            ->addSelect("paises.nombre_pais as pais")
            ->addSelect("paises.codigo_pais as codigo_pais");
        if ($country) {
            $listciudades
                ->where("paises.codigo_pais", $country);
        }
        if ($id) {
            $listciudades->where('municipios.id', $id);
        }
        if ($department) {
            $listciudades->where('id_departamento', $department);
        }
        if ($name) {
            $listciudades->where('municipios.nombre', $name);

        }
        if ($indicative) {
            $listciudades->where('municipios.indicativo', $indicative);

        }
        $listciudades = $listciudades->get();

        $ciudades = array();

        foreach ($listciudades as $row) {
            $ciudades[] = array("id" => $row->id, 'name' => $row->nombre, "department" => $row->id_departamento, "departmentName" => $row->nombre_departamento, 'indicative' => $row->indicativo, "country" => $row->pais, "country_code" => $row->codigo_pais, "dane" => $row->cod_dian);
        }

        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "city list sucess",
            'lastAction' => "query city",
            'data' => $ciudades,
        );

    }

    public function getCitiesPrincipal(Request $request)
    {

        $filter = (object)$request->get("filter", "");
        $id = isset($filter->cod_dane) ? $filter->cod_dane : "";
        $department = isset($filter->department) ? $filter->department : "";
        $name = isset($filter->name) ? $filter->name : "";

        $listciudades = DB::table("logistica_ciudades");
        if ($id) {
            $listciudades->where('codigo_dane', $id);
        }
        if ($department) {
            $listciudades->where('departamento', "like", "%{$department}%");
        }
        if ($name) {
            $listciudades->where('nombre', $name);
        }
        $listciudades = $listciudades->get();

        $ciudades = array();

        foreach ($listciudades as $row) {
            $ciudades[] = array("id" => $row->codigo_dane, 'name' => $row->nombre, "department" => $row->departamento);
        }

        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "city list sucess",
            'lastAction' => "query city",
            'data' => $ciudades,
        );

    }

    public function getCategories(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";

        $listcategorias = DB::table("categorias");
        if ($id) {
            $listcategorias->where('id', $id);
        }
        if ($name) {
            $listcategorias->where('nombre', $name);
        }
        $listcategorias = $listcategorias->get();

        $categorias = array();
        foreach ($listcategorias as $row) {
            $categorias[] = array("id" => $row->id, 'name' => $row->nombre);
        }
        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "category list success",
            'lastAction' => "query category",
            'data' => $categorias,
        );
    }

    public function getSubCategories(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";
        $categoryId = isset($filter->categoryId) ? $filter->categoryId : "";

        $listSubcategories = DB::table("subcategorias");

        if ($id) {
            $listSubcategories->where("id", $id);
        }
        if ($name) {
            $listSubcategories->where("nombre", $name);
        }
        if ($categoryId) {
            $listSubcategories->where("id_categoria", $categoryId);
        }
        $listSubcategories = $listSubcategories->get();
        $subcategories = array();

        foreach ($listSubcategories as $row) {
            $subcategories[] = array("id" => $row->id, "name" => $row->nombre, "categoryId" => $row->id_categoria);
        }

        return array(
            'success' => true,
            'title_response' => "OK",
            'text_response' => "Subcategorias consultadas exitosamente",
            'last_action' => "Query Subcategorias",
            'data' => $subcategories,

        );
    }

    public function getNomenclature(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";
        $abbreviation = isset($filter->abbreviation) ? $filter->abbreviation : "";

        $listNomenclature = DB::table("direcciones_nomenclatura");
        if ($id) {
            $listNomenclature->where('id', $id);
        }
        if ($name) {
            $listNomenclature->where('nombre', $name);
        }
        if ($abbreviation) {
            $listNomenclature->where('abreviatura', $abbreviation);
        }
        $listNomenclature = $listNomenclature->get();

        $nomenclatures = array();
        foreach ($listNomenclature as $row) {
            $nomenclatures[] = array("id" => $row->id, 'name' => $row->nombre, "abbreviation" => $row->abreviatura);
        }
        return array(
            'success' => true,
            'titleResponse' => "OK",
            'textResponse' => "nomenclature list success",
            'lastAction' => "query nomenclature",
            'data' => $nomenclatures,
        );
    }

    public function getProfessions(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";
        $code = isset($filter->code) ? $filter->code : "";

        $listProfessions = DB::table("profesiones");
        if ($id) {
            $listProfessions->where("id", $id);
        }
        if ($code) {
            $listProfessions->where("codigo", $code);
        }
        if ($name) {
            $listProfessions->where("nombre", $name);
        }
        $listProfessions = $listProfessions->get();

        $professions = array();

        foreach ($listProfessions as $row) {
            $professions[] = array("id" => $row->id, 'code' => $row->codigo, 'name' => $row->nombre);
        }

        return array(
            'success' => true,
            'titleResponse' => "Professions list",
            'textResponse' => "Professions list success",
            'lastAction' => "Query professions",
            'data' => $professions,
        );

    }

    public function getTypeAccounts(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $type = isset($filter->type) ? $filter->type : "";
        $description = isset($filter->description) ? $filter->description : "";
        $code = isset($filter->code) ? $filter->code : "";

        $listTypeAccounts = DB::table("tipos_cuenta");
        if ($id) {
            $listTypeAccounts->where("id", $id);
        }
        if ($description) {
            $listTypeAccounts->where("descripcion", $description);
        }
        if ($type) {
            $listTypeAccounts->where("tipo", $type);
        }
        if ($code) {
            $listTypeAccounts->where("codigo", $code);
        }
        $listTypeAccounts = $listTypeAccounts->get();

        $typeAccounts = array();

        foreach ($listTypeAccounts as $row) {
            $typeAccounts[] = array("id" => $row->id, 'type' => $row->tipo, 'description' => $row->descripcion, 'code' => $row->codigo);
        }

        return array(
            'success' => true,
            'titleResponse' => "Type account list",
            'textResponse' => "Type account list success",
            'lastAction' => "Query type account",
            'data' => $typeAccounts,
        );
    }

    public function getPropertyTypes(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";

        $listPropertyTypes = DB::table("direcciones_tipo_propiedad");
        if ($id) {
            $listPropertyTypes->where("id", $id);
        }
        if ($name) {
            $listPropertyTypes->where("nombre", $name);
        }
        $listPropertyTypes = $listPropertyTypes->get();

        $propertyTypes = array();

        foreach ($listPropertyTypes as $row) {
            $propertyTypes[] = array("id" => $row->id, 'name' => $row->nombre);
        }

        return array(
            'success' => true,
            'titleResponse' => "Property types list",
            'textResponse' => "Property types list success",
            'lastAction' => "Query property type",
            'data' => $propertyTypes,
        );
    }

    public function getSocialNetworks(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";

        $listSocialNetworks = DB::table("redessociales");
        if ($id) {
            $listSocialNetworks->where("id", $id);
        }
        if ($name) {
            $listSocialNetworks->where("nombre", $name);
        }
        $listSocialNetworks = $listSocialNetworks->get();

        $socialNetworks = array();

        foreach ($listSocialNetworks as $row) {
            $socialNetworks[] = array("id" => $row->id, 'name' => $row->nombre);
        }

        return array(
            'success' => true,
            'titleResponse' => "Social networks list",
            'textResponse' => "Social networks list success",
            'lastAction' => "Query social networks",
            'data' => $socialNetworks,
        );
    }

    public function getMerchantTypes(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";

        $listMerchantTypes = DB::table("tipo_comercio");
        if ($id) {
            $listMerchantTypes->where("id", $id);
        }
        if ($name) {
            $listMerchantTypes->where("nombre", $name);
        }
        $listMerchantTypes = $listMerchantTypes->get();

        $merchantTypes = array();

        foreach ($listMerchantTypes as $row) {
            $merchantTypes[] = array("id" => $row->id, 'name' => $row->nombre);
        }

        return array(
            'success' => true,
            'titleResponse' => "Merchant types list",
            'textResponse' => "Merchant types list success",
            'lastAction' => "Query social merchant types",
            'data' => $merchantTypes,
        );
    }


    public function getBancos(Request $request)
    {

        /**
         * @var $arClient Clientes
         */
        $arBank = $this->banksOrderByName();

        $arClient = Clientes::find($request->get("clientId"));

        if ($arClient->id_aliado == 20775) {
            $arBank = Bancos::where('id', 1421)->get()->toArray();
            $arBank = (array)$arBank;

        }

        return array(
            'success' => true,
            'titleResponse' => "bank list",
            'textResponse' => "bank list success",
            'lastAction' => "Query bank list",
            'data' => $arBank,
        );
    }

    public function getTipoDocumentos()
    {
        $tiposDocumentos = TipoDocumentos::where("activo", 1)->get();
        $typeDoc = [];
        foreach ($tiposDocumentos as $key => $tiposDocumento) {
            $typeDoc[] = ["id" => $tiposDocumento->id, "name" => $tiposDocumento->nombre, "cod" => $tiposDocumento->codigo, "description" => $tiposDocumento->descripcion];
        }

        return array(
            'success' => true,
            'titleResponse' => "document type list",
            'textResponse' => "document type list success",
            'lastAction' => "Query document type list",
            'data' => $typeDoc,
        );
    }


    private function banksOrderByName($pais = "CO")
    {
        $result = Bancos::where("pais", $pais)
            ->select("id")
            ->addSelect("nombre as name")
            ->where("codigo_banco", "!=", "''")
            ->where("activo", true)
            ->orderBy("nombre", "ASC")->get()->toArray();


        return (array)$result;
    }


    private function productValidation($clienteId)
    {

        $data = (object)[
            "tipoIdentificacion" => "",
            "numeroIdentificacion" => "",
            "accountType" => "",
            "reference" => ""
        ];

        /**
         * @var $arClient Clientes
         * @var $arTypeDocument TipoDocumentos
         */
        if ($data->tipoIdentificacion === "" && $data->numeroIdentificacion === "") {
            $arClient = Clientes::find($clienteId);
            $arTypeDocument = TipoDocumentos::find($arClient->tipo_doc);
            $data->numeroIdentificacion = $arClient->tipo_doc == 1 ? (int)$arClient->documento : $arClient->documento;
            if ($arTypeDocument) {
                $data->tipoIdentificacion = $arTypeDocument->codigo;
            } else {
                $data->tipoIdentificacion = "CC";
            }
            if ($arClient->tipo_cliente === "C") {
                $data->numeroIdentificacion = $arClient->documento . "" . $arClient->digito;
            }
        }

        if ($data->reference === "" && $data->accountType === "") {
            $data->accountType = "ALL";
            $data->reference = "0000";
        }


        //Si el tipo de cuenta son todas la referencia debe quedar en 0000
        if ($data->accountType == "ALL") {
            $data->reference = "0000";
        }


        $baseUrlRest = getenv("BASE_URL_REST");
        $productoValidation = $this->productValitation($data->tipoIdentificacion, $data->numeroIdentificacion, $data->reference, $data->accountType, $baseUrlRest);
        $return = [];
        if (isset($productoValidation[0])) {
            $return = (array)($productoValidation[0]);
        }

        return $return;

    }

    public function accountType(Request $request)
    {
        $accountType = [
            ["id" => "CA", "name" => "Cuenta de Ahorros"],
            ["id" => "CC", "name" => "Cuenta de Corriente"],
            ["id" => "CA", "name" => "Daviplata"]
        ];

        return array(
            'success' => true,
            'titleResponse' => "accountType list",
            'textResponse' => "accountType list success",
            'lastAction' => "Query accountType list",
            'data' => $accountType,
        );
    }


    public function typeDomain(Request $request)
    {
        $types = DominioConfiguracionProyecto::all();

        $typeList = [];
        foreach ($types as $type) {
            array_push($typeList, array("id" => $type->id, "name" => $type->nombre));
        }

        return array(
            'success' => true,
            'titleResponse' => "typeDomain list",
            'textResponse' => "typeDomain list success",
            'lastAction' => "Query typeDomain list",
            'data' => $typeList,
        );
    }

    public function getCountries()
    {
        $countries = $this->getMasterCountries();
        $data["paises"] = [];
        if (isset($countries->paises)) {
            foreach ($countries->paises as $country) {
                $indicativeId = strtolower($country->id);
                $countryPush = [
                    "id" => $country->indicativo,
                    "name" => $country->id,
                    "title" => $country->nombre,
                    "displayText" => $country->id . "(+" . $country->indicativo . ")",
                    "locale" => 'es-ES',
                    "flag" => "/img/flags/{$indicativeId}.svg"
                ];
                array_push($data["paises"], $countryPush);
            }
        } else {
            $indicativeId = strtolower("CO");
            $countryPush = [
                "id" => "57",
                "name" => "CO",
                "title" => "Colombia",
                "displayText" => "CO" . "(+" . "57" . ")",
                "locale" => 'es-ES',
                "flag" => "/img/flags/{$indicativeId}.svg"
            ];
            array_push($data["paises"], $countryPush);
        }

        return array(
            'success' => true,
            'titleResponse' => "countries list",
            'textResponse' => "countries list success",
            'lastAction' => "Query countries list",
            'data' => $data["paises"],
        );
    }

    public function setLog(Request $request)
    {
        try {
            $parameters = $request->request->all();
            $cliente_id = $parameters["cliente_id"];
            $accion = $parameters["accion"];

            $id = uniqid('', true);
            $util = new Utils();

            $log = new LogRest();
            $log->session_id = $id;
            $log->cliente_id = $cliente_id;
            $log->fechainicio = new \DateTime('now');
            $log->request = json_encode($request);
            $log->microtime = $util->microtime_float();
            $log->ip = $util->getRealIP();

            if ($accion != "") {
                $log->accion = $accion;
            }
            $log->save();

        } catch (\Exception $exception) {

        }


    }

    public function getTransactionStatus(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";

        $listTypesCodresponse = DB::table("tipo_cod_respuestas");
        if ($id) {
            $listTypesCodresponse->where("id", $id);
        }
        if ($name) {
            $listTypesCodresponse->where("nombre", $name);
        }
        $listTypesCodresponse = $listTypesCodresponse->get();

        $statusTransaction = array();

        foreach ($listTypesCodresponse as $row) {
            $statusTransaction[] = array("id" => $row->id, 'name' => $row->nombre);
        }

        return array(
            'success' => true,
            'titleResponse' => "Transactions status list",
            'textResponse' => "Transaction status list success",
            'lastAction' => "Query property type",
            'data' => $statusTransaction,
        );
    }

    public function getTransactionEnviroment()
    {
        $transactionEnviroment = [
            ["id" => 1, "name" => "En Pruebas"],
            ["id" => 2, "name" => "Producción"],
        ];

        return array(
            'success' => true,
            'titleResponse' => "Transactions enviroment list",
            'textResponse' => "Transaction enviroment list success",
            'lastAction' => "Query property type",
            'data' => $transactionEnviroment,
        );
    }

    public function getTransactionPaymentMethods(Request $request)
    {
        $filter = (object)$request->get("filter", "");
        $id = isset($filter->id) ? $filter->id : "";
        $name = isset($filter->name) ? $filter->name : "";

        $listPaymentMethods = DB::table("medios_pago");
        if ($id) {
            $listPaymentMethods->where("Id", $id);
        }
        if ($name) {
            $listPaymentMethods->where("nombre", $name);
        }
        $listPaymentMethods = $listPaymentMethods->get();

        $paymentMethods = array();

        foreach ($listPaymentMethods as $row) {
            $paymentMethods[] = array("id" => $row->Id, 'name' => $row->nombre);
        }

        return array(
            'success' => true,
            'titleResponse' => "Transactions payment methods list",
            'textResponse' => "Transaction payment methods list success",
            'lastAction' => "Query property type",
            'data' => $paymentMethods,
        );
    }

    public function responsabilidades_fiscales(Request $request)
    {
        try {

            $arResponsabilidadFiscales = DB::table('responsabilidad_fiscal')->get();

            $responsabilidadFiscal = $this->responsabilidadFiscal($arResponsabilidadFiscales);

            return array(
                'success' => true,
                'titleResponse' => "Fiscal responsability list",
                'textResponse' => "Fiscal responsability list success",
                'lastAction' => "consult fiscal responsabilities",
                'data' => $responsabilidadFiscal,
            );
        } catch (\Exception $exception) {
            return $exception;
        }
    }

    /**
     * @return array
     */
    public function getNetworks(): array
    {
        $networks = [
            [
                'id' => 2,
                'name' => 'Redeban',
                'description' => 'Redeban Multicolor (Red de procesamiento Tarjeta de crédito)',
            ],
            [
                'id' => 4,
                'name' => 'Credibanco (Pago automático)',
                'description' => 'Credibanco (Pago automático)',
            ],
            [
                'id' => 5,
                'name' => 'PSE avanza',
                'description' => 'PSE avanza',
            ],
            [
                'id' => 8,
                'name' => 'Credibanco VNP',
                'description' => 'Credibanco VNP',
            ],
        ];

        return [
            'success' => true,
            'titleResponse' => 'Networks list',
            'textResponse' => 'Networks list success',
            'lastAction' => 'consult networks list',
            'data' => $networks,
        ];
    }

    /**
     * @param $arResponsabilidadFiscales ResponsabilidadFiscal
     */
    public function responsabilidadFiscal($arResponsabilidadFiscales)
    {
        $responsabilidadFiscal = [
            "PN" => [

            ],
            "PJ" => [

            ]
        ];

        foreach ($arResponsabilidadFiscales as $arResponsabilidadFiscal) {
            $clasificaciones = explode(",", $arResponsabilidadFiscal->clasificacion);
            $subResponsabilidades = $arResponsabilidadFiscal->sub_responsabilidad ? explode(",", $arResponsabilidadFiscal->sub_responsabilidad) : null;
            if ($arResponsabilidadFiscal->tipo_persona && (isset($clasificaciones) && $clasificaciones[0] !== "")) {
                $responsabilidad = [
                    "id" => $arResponsabilidadFiscal->id,
                    "nombre" => $arResponsabilidadFiscal->nombre,
                    "codigo" => $arResponsabilidadFiscal->codigo_responsabilidad_fiscal
                ];
                if ($arResponsabilidadFiscal->tipo_persona === "ambos") {
                    foreach ($clasificaciones as $clasificacion) {
                        if (!isset($responsabilidadFiscal["PJ"][$clasificacion])) $responsabilidadFiscal["PJ"][$clasificacion] = [];
                        if (!isset($responsabilidadFiscal["PN"][$clasificacion])) $responsabilidadFiscal["PN"][$clasificacion] = [];

                        array_push($responsabilidadFiscal["PJ"][$clasificacion], $responsabilidad);
                        array_push($responsabilidadFiscal["PN"][$clasificacion], $responsabilidad);

                        if ($subResponsabilidades) {
                            $countResponsabilidadesPJ = count($responsabilidadFiscal["PJ"][$clasificacion]) - 1;
                            $countResponsabilidadesPN = count($responsabilidadFiscal["PN"][$clasificacion]) - 1;
                            foreach ($subResponsabilidades as $subResponsabilidad) {
                                $reponsabilidadFiscal = DB::table('responsabilidad_fiscal');
                                $arResponsabilidadFiscalSub = $reponsabilidadFiscal->find($subResponsabilidad);
                                $auto = false;
                                $disabled = false;
                                if ($arResponsabilidadFiscal->id === 5) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                    if ($arResponsabilidadFiscalSub->id === 12) {
                                        $auto = true;
                                        $disabled = true;
                                    }
                                } else if ($arResponsabilidadFiscal->id === 7) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                    if ($arResponsabilidadFiscalSub->id === 12) {
                                        $auto = true;
                                        $disabled = true;
                                    }


                                    if ($arResponsabilidadFiscalSub->id === 5) {
                                        $auto = true;
                                    }
                                } else if ($arResponsabilidadFiscal->id === 117) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                }
                                $subResponsabilidadObject = [
                                    "id" => $arResponsabilidadFiscalSub->id,
                                    "nombre" => $arResponsabilidadFiscalSub->nombre,
                                    "codigo" => $arResponsabilidadFiscalSub->codigo_responsabilidad_fiscal,
                                    "auto" => $auto,
                                    "disabled" => $disabled
                                ];

                                if (!isset($responsabilidadFiscal["PJ"][$clasificacion][$countResponsabilidadesPJ]["sub"])) $responsabilidadFiscal["PJ"][$clasificacion][$countResponsabilidadesPJ]["sub"] = [];
                                if (!isset($responsabilidadFiscal["PN"][$clasificacion][$countResponsabilidadesPN]["sub"])) $responsabilidadFiscal["PN"][$clasificacion][$countResponsabilidadesPN]["sub"] = [];
                                array_push($responsabilidadFiscal["PJ"][$clasificacion][$countResponsabilidadesPJ]["sub"], $subResponsabilidadObject);
                                array_push($responsabilidadFiscal["PN"][$clasificacion][$countResponsabilidadesPN]["sub"], $subResponsabilidadObject);

                            }
                        }

                    }
                } else if ($arResponsabilidadFiscal->tipo_persona === "juridica") {

                    foreach ($clasificaciones as $clasificacion) {
                        if (!isset($responsabilidadFiscal["PJ"][$clasificacion])) $responsabilidadFiscal["PJ"][$clasificacion] = [];
                        array_push($responsabilidadFiscal["PJ"][$clasificacion], $responsabilidad);

                        if ($subResponsabilidades) {
                            $countResponsabilidadesPJ = count($responsabilidadFiscal["PJ"][$clasificacion]) - 1;
                            foreach ($subResponsabilidades as $subResponsabilidad) {
                                $reponsabilidadFiscal = DB::table('responsabilidad_fiscal');
                                $arResponsabilidadFiscalSub = $reponsabilidadFiscal->find($subResponsabilidad);

                                $auto = false;
                                $disabled = false;

                                if ($arResponsabilidadFiscal->id === 5) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                    if ($arResponsabilidadFiscalSub->id === 12) {
                                        $auto = true;
                                        $disabled = true;
                                    }
                                } else if ($arResponsabilidadFiscal->id === 7) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                    if ($arResponsabilidadFiscalSub->id === 12) {
                                        $auto = true;
                                        $disabled = true;
                                    }


                                    if ($arResponsabilidadFiscalSub->id === 5) {
                                        $auto = true;
                                    }
                                } else if ($arResponsabilidadFiscal->id === 117) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                }
                                $subResponsabilidadObject = [
                                    "id" => $arResponsabilidadFiscalSub->id,
                                    "nombre" => $arResponsabilidadFiscalSub->nombre,
                                    "codigo" => $arResponsabilidadFiscalSub->codigo_responsabilidad_fiscal,
                                    "auto" => $auto,
                                    "disabled" => $disabled
                                ];

                                if (!isset($responsabilidadFiscal["PJ"][$clasificacion][$countResponsabilidadesPJ]["sub"])) $responsabilidadFiscal["PJ"][$clasificacion][$countResponsabilidadesPJ]["sub"] = [];
                                array_push($responsabilidadFiscal["PJ"][$clasificacion][$countResponsabilidadesPJ]["sub"], $subResponsabilidadObject);

                            }
                        }
                    }
                } else {
                    foreach ($clasificaciones as $clasificacion) {
                        if (!isset($responsabilidadFiscal["PJ"][$clasificacion])) $responsabilidadFiscal["PN"][$clasificacion] = [];
                        array_push($responsabilidadFiscal["PN"][$clasificacion], $responsabilidad);

                        if ($subResponsabilidades) {
                            $countResponsabilidadesPN = count($responsabilidadFiscal["PN"][$clasificacion]) - 1;
                            foreach ($subResponsabilidades as $subResponsabilidad) {

                                $reponsabilidadFiscal = DB::table('responsabilidad_fiscal');
                                $arResponsabilidadFiscalSub = $reponsabilidadFiscal->find($subResponsabilidad);
                                $auto = false;
                                $disabled = false;
                                if ($arResponsabilidadFiscal->id === 5) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                    if ($arResponsabilidadFiscalSub->id === 12) {
                                        $auto = true;
                                        $disabled = true;
                                    }
                                } else if ($arResponsabilidadFiscal->id === 7) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                    if ($arResponsabilidadFiscalSub->id === 12) {
                                        $auto = true;
                                        $disabled = true;
                                    }


                                    if ($arResponsabilidadFiscalSub->id === 5) {
                                        $auto = true;
                                    }
                                } else if ($arResponsabilidadFiscal->id === 117) {
                                    if ($arResponsabilidadFiscalSub->id === 2) {
                                        $auto = true;
                                        $disabled = true;
                                    }

                                }
                                $subResponsabilidadObject = [
                                    "id" => $arResponsabilidadFiscalSub->id,
                                    "nombre" => $arResponsabilidadFiscalSub->nombre,
                                    "codigo" => $arResponsabilidadFiscalSub->codigo_responsabilidad_fiscal,
                                    "auto" => $auto,
                                    "disabled" => $disabled
                                ];

                                if (!isset($responsabilidadFiscal["PN"][$clasificacion][$countResponsabilidadesPN]["sub"])) $responsabilidadFiscal["PN"][$clasificacion][$countResponsabilidadesPN]["sub"] = [];
                                array_push($responsabilidadFiscal["PN"][$clasificacion][$countResponsabilidadesPN]["sub"], $subResponsabilidadObject);

                            }
                        }
                    }
                }


            }
        }

        return $responsabilidadFiscal;
    }


    public function ciiu(Request $request)
    {
        try {
            $arCiiu = DB::table('codigo_ciiu')->get();

            $ciiu = [];
            foreach ($arCiiu as $arc) {
                $ciiu[] = [
                    "id" => $arc->id,
                    "name" => "{$arc->codigo} / {$arc->descripcion}"
                ];
            };

            return array(
                'success' => true,
                'titleResponse' => "Ciiu list",
                'textResponse' => "Ciiu list success",
                'lastAction' => "consult Ciiu",
                'data' => $ciiu,
            );
        } catch (\Exception $exception) {
            return $exception;
        }
    }

    public function reconocimientoPublico()
    {
        $reconocimientoPublico = [
            [
                "id" => 0,
                "name" => "No"
            ],
            [
                "id" => 1,
                "name" => "Si, cargo público o relacionado a recursos públicos"
            ],
            [
                "id" => 2,
                "name" => "Si, goza de reconocimiento público"
            ]
        ];

        return array(
            'success' => true,
            'titleResponse' => "Public recognition list",
            'textResponse' => "Public recognition list success",
            'lastAction' => "consult public recognition",
            'data' => $reconocimientoPublico,
        );
    }

    public function ticketPrioridades(){
        $tckPrioridades = TckPrioridad::all();
        return array(
            'success' => true,
            'titleResponse' => "ticket priorities list",
            'textResponse' => "ticket priorities list success",
            'lastAction' => "Query ticket priorities list",
            'data' => $tckPrioridades
        );
    }

    public function ticketDepartamentos(){
        $tckDepartamentos = TckDepartamentos::all();
        return array(
            'success' => true,
            'titleResponse' => "ticket departments list",
            'textResponse' => "ticket departments list success",
            'lastAction' => "Query ticket departments list",
            'data' => $tckDepartamentos
        );
    }
}