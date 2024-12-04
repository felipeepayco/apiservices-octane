<?php

namespace App\Listeners;


use App\Events\ConsultSellListEvent;
use App\Events\CatalogueProductNewEvent;
use App\Events\ValidationGeneralSellListEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use App\Models\Catalogo;
use App\Models\CatalogoProductos;
use App\Models\CatalogoCategorias;
use App\Models\CatalogoProductosCategorias;

use App\Models\CatalogoProductosEnvio;
use App\Models\CatalogoProductosFile;
use App\Models\CatalogoProductosFiles;
use App\Models\CatalogoProductosReferencias;
use App\Models\CatalogoProductosReferenciasFiles;
use App\Models\CatalogoTipoEnvio;
use App\Models\CompartirCobro;
use App\Models\FilesCobro;
use App\Models\Trm;
use App\Models\WsFiltrosClientes;
use App\Models\WsFiltrosDefault;
use Illuminate\Http\Request;
use App\Helpers\Messages\CommonText;

class CatalogueProductNewListener extends HelperPago
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
    public function handle(CatalogueProductNewEvent $event)
    {

        try {
            $fieldValidation = $event->arr_parametros; 
            $clientId = $fieldValidation["clientId"];
            $quantity = $fieldValidation["quantity"];
            $reference = $fieldValidation["reference"];
            $onePayment = $fieldValidation["onePayment"];
            $amount = $fieldValidation["amount"];
            $currency = $fieldValidation["currency"];
            $id = $fieldValidation["id"];
            $available = $fieldValidation["available"];
            $base = isset($fieldValidation["baseTax"]) ? $fieldValidation["baseTax"] : null;
            $sellType = 1;
            $catalogoId = $fieldValidation["catalogueId"];

            $description = $fieldValidation["description"];
            $title = $fieldValidation["title"];
            $urlConfirmation = $fieldValidation["urlConfirmation"];
            $urlResponse = $fieldValidation["urlResponse"];
            $tax = isset($fieldValidation["tax"]) && $fieldValidation["tax"] !== "" ? $fieldValidation["tax"] : 0;
            $expirationDate = $fieldValidation["expirationDate"];
            $img = $fieldValidation["img"];
            $document = $fieldValidation["document"];
            $email = isset($fieldValidation["email"]) ? $fieldValidation["email"] : "";
            $cellPhone = isset($fieldValidation["mobilePhone"]) ? $fieldValidation["mobilePhone"] : "";
            $indicativo = isset($fieldValidation["indicative"]) ? $fieldValidation["indicative"] : "";
            $nombreContacto = isset($fieldValidation["contactName"]) ? $fieldValidation["contactName"] : null;
            $numeroContacto = isset($fieldValidation["contactNumber"]) ? $fieldValidation["contactNumber"] : null;
            $productReferences = isset($fieldValidation["productReferences"]) ? $fieldValidation["productReferences"] : "";
            $tiposEnvios = $fieldValidation["shippingTypes"];
            $tiposEnviosArray = [];

            $nueva_categoria = "";
            $categorias = array();

            if ($reference == "") {
                $reference = time();
            }
            if ($id == "") {
                $id = 0;
            }


            if ($quantity == 1) {
                $onePayment = 1;
                if ($reference == "") {
                    $reference = \uniqid($clientId);
                }
            }





            //Nueva categoria
            if (isset($fieldValidation["category_new"])) {
                $nueva_categoria = $fieldValidation["category_new"];
            }

            if (isset($fieldValidation["categories"])) {
                $categorias = $fieldValidation["categories"];
            }


            //Fin validación maximo
            /** @var  $cobro Cobros */
            if ($id > 0) {
                $cobro = CatalogoProductos::where('id', $id)->where("cliente_id", $clientId)->get()->first();
                $esnuevo = false;
                if (!$cobro) {
                    $cobro = false;
                }

            } else {

                $cobro = new CatalogoProductos();
                $txtcodigo = 1;
                $cobro->fecha = new \DateTime('now');
                $cobro->estado = 1;
                $cobro->txtcodigo = $txtcodigo;
                $esnuevo = true;
            }

            if ($esnuevo) {
                $existe = CatalogoProductos::where("numerofactura", $reference)
                    ->where("cliente_id", $clientId)
                    ->first();
            }
            if (isset($existe) && $existe) {
                $success = false;
                $title_response = 'Error';
                $text_response = "There is already a payment with the same reference number or invoice:'$reference'";
                $last_action = 'create new sell';
                $error = "Error create sell";
                $data = [];

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;
            }

            if (!$cobro && $id > 0) {
                $success = false;
                $title_response = 'Error';
                $text_response = "Product not exist'";
                $last_action = 'query database ';
                $error = "Error update product";
                $data = [];

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;
            }

            $catalogoExiste = Catalogo::where("id", $catalogoId)
                ->where("estado", 1)
                ->where("cliente_id", $clientId)
                ->first();
            if (!$catalogoExiste) {
                $success = false;
                $title_response = 'Error';
                $text_response = "Catalogue not exist'";
                $last_action = 'query database ';
                $error = "Error create product";
                $data = [];

                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;
            }

            $cobro->cliente_id = $clientId;
            $cobro->cobrounico = $onePayment;
            $cobro->cantidad = $quantity;
            $cobro->disponible = $available;
            $cobro->base_iva = isset($base) ? $base : 0;
            $cobro->descripcion = $description;
            $cobro->titulo = $title;
            $cobro->moneda = $currency;
            $cobro->tipocobro = 1;
            $cobro->url_confirmacion = $urlConfirmation;
            $cobro->url_respuesta = $urlResponse;

            $cobro->iva = $tax;
            $cobro->valor = $amount;
            $cobro->numerofactura = $reference;
            $cobro->estado = 1;
            $cobro->fecha_expiracion = $expirationDate;
            $cobro->rutaqr = "";
            $cobro->catalogo_id = $catalogoId;
            $cobro->nombre_contacto = $nombreContacto;
            $cobro->numero_contacto = $numeroContacto;
            $cobro->save();
            $imagenes = [];
            if ($img) {
                if (is_object($img)) {
                    $img = (array)$img;;
                }
                $totalImg = count($img);
                if ($totalImg > 10) {
                    $success = false;
                    $title_response = 'number of files exceeded';
                    $text_response = 'number of files exceeded';
                    $last_action = 'number of files exceeded';
                    $data = [];
                    $arr_respuesta['success'] = $success;
                    $arr_respuesta['titleResponse'] = $title_response;
                    $arr_respuesta['textResponse'] = $text_response;
                    $arr_respuesta['lastAction'] = $last_action;
                    $arr_respuesta['data'] = $data;

                    return $arr_respuesta;
                }
                if ($totalImg > 0) {
                    for ($k = 0; $k < $totalImg; $k++) {
                        $data = explode(',', $img[$k]);
                        $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
                        $sacarExt = explode('image/', $data[0]);
                        $sacarExt = explode(';', $sacarExt[1]);

                        if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
                            $success = false;
                            $title_response = 'file format not allowed';
                            $text_response = 'file format not allowed';
                            $last_action = 'file format not allowed';
                            $data = [];
                            $arr_respuesta['success'] = $success;
                            $arr_respuesta['titleResponse'] = $title_response;
                            $arr_respuesta['textResponse'] = $text_response;
                            $arr_respuesta['lastAction'] = $last_action;
                            $arr_respuesta['data'] = $data;
                        }
                        $base64 = base64_decode($data[1]);
                        file_put_contents(
                            $tmpfname . "." . $sacarExt[0],
                            $base64
                        );


                        $fechaActual = new \DateTime('now');


                        //Subir los archivos
                        $nameFile = "{$clientId}_cobros_files_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";
                        $urlFile = "catalogo/files/{$nameFile}";

                        $catalogueProductReferenceFile = CatalogoProductosFiles::where("catalogo_productos_id", $cobro->id)
                            ->where("posicion", $k)
                            ->where("estado", 1)
                            ->first();
                        if (!$catalogueProductReferenceFile) $catalogueProductReferenceFile = new CatalogoProductosFiles();
                        $catalogueProductReferenceFile->url = $urlFile;
                        $catalogueProductReferenceFile->catalogo_productos_id = $cobro->id;
                        $catalogueProductReferenceFile->posicion = $k;
                        $catalogueProductReferenceFile->estado = true;
                        $catalogueProductReferenceFile->save();

                        //Subir a rackespace
                        $this->uploadFile('media', $tmpfname . "." . $sacarExt[0], $nameFile, 'catalogo/files');
                        $imagenes[] = getenv("RACKSPACE_CONTAINER_BASE_PUBLIC_URL") . "/" . $urlFile;

                        unlink($tmpfname . "." . $sacarExt[0]);
                    }
                }
            }

            CatalogoProductosEnvio::where("id_catalogo_productos", $cobro->id)
                ->update(["estado" => 0]);

            if (is_array($tiposEnvios) || is_object($tiposEnvios)) {
                for ($i = 0; $i < count($tiposEnvios); $i++) {
                    $existe2 = CatalogoTipoEnvio::where("nombre", $tiposEnvios[$i]["type"])
                        ->where("estado", 1)->first();
                    if (!$existe2) {
                        $arrResponse['success'] = false;
                        $arrResponse['titleResponse'] = "I couldn't get the shipping types";
                        $arrResponse['textResponse'] = "I couldn't get the shipping types";
                        $arrResponse['lastAction'] = "create reference";
                        $arrResponse['data'] = ["error" => "shipping types not found"];

                        return $arrResponse;
                    }


//                    if (!$catalogoProductosEnvio){
                    $catalogoProductosEnvio = new CatalogoProductosEnvio();
                    $catalogoProductosEnvio->id_catalogo_productos = $cobro->id;
//                    }
                    $catalogoProductosEnvio->estado = 1;
                    if ($tiposEnvios[$i]["type"] != "contraentrega" && $tiposEnvios[$i]["type"] != "negociable") {
                        if (!isset($tiposEnvios[$i]["amount"])) {
                            $arrResponse['success'] = false;
                            $arrResponse['titleResponse'] = "Property amount the shipping types";
                            $arrResponse['textResponse'] = "Property amount the shipping types";
                            $arrResponse['lastAction'] = "create reference";
                            $arrResponse['data'] = ["error" => "Property amount the shipping types not found"];

                            return $arrResponse;
                        }
                    }

                    $catalogoProductosEnvio->valor = $tiposEnvios[$i]["amount"];
                    $catalogoProductosEnvio->id_catalogo_tipo_envio = $existe2->id;
                    $catalogoProductosEnvio->save();

                    $tiposEnviosArray[] = [
                        "type" => $existe2->nombre,
                        "amount" => $catalogoProductosEnvio->valor
                    ];
                }
            } else {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = "I couldn't get the shipping types";
                $arrResponse['textResponse'] = "I couldn't get the shipping types";
                $arrResponse['lastAction'] = "create reference";
                $arrResponse['data'] = ["error" => "shipping types not found"];

                return $arrResponse;
            }


            $productReferencesArray = [];


            $referenceFiles = CatalogoProductosReferencias::where("id_catalogo_productos", $cobro->id)
                ->where("estado", 1)
                ->select("id")
                ->get()->toArray();

            foreach ($referenceFiles as $referenceFile) {
                CatalogoProductosReferenciasFiles::where("id_catalogo_productos_referencias", $referenceFile["id"])
                    ->update(["estado" => 0]);
            }

            CatalogoProductosReferencias::where("id_catalogo_productos", $cobro->id)
                ->update(["estado" => 0]);


            //referencias de un producto
            if ($productReferences) {
                for ($i = 1; $i <= 10; $i++) {
                    $position = "reference" . $i;
                    if (isset($productReferences[$position])) {
                        if (!isset($productReferences[$position]["id"])) {


                            $catalogueProductReference = new CatalogoProductosReferencias();
                            $catalogueProductReference->nombre = $productReferences[$position]["name"];
                            $catalogueProductReference->valor = $productReferences[$position]["amount"];
                            $catalogueProductReference->cantidad = $productReferences[$position]["quantity"];
                            $catalogueProductReference->disponible = $productReferences[$position]["quantity"];
                            $catalogueProductReference->estado = true;
                            $catalogueProductReference->fecha_creacion = new \DateTime("now");
                            $catalogueProductReference->id_catalogo_productos = $cobro->id;
                            $catalogueProductReference->cobrounico = $onePayment;
                            $catalogueProductReference->descripcion = $description;
                            $catalogueProductReference->base_iva = isset($base) ? $base : 0;
                            $catalogueProductReference->moneda = $currency;
                            $catalogueProductReference->tipocobro = 1;
                            $catalogueProductReference->url_confirmacion = $urlConfirmation;
                            $catalogueProductReference->url_respuesta = $urlResponse;
                            $catalogueProductReference->iva = $tax;
                            $catalogueProductReference->numerofactura = $reference;
                            $catalogueProductReference->fecha_expiracion = $expirationDate;
                            $catalogueProductReference->rutaqr = "";

                            $txtcodigo = str_pad($cobro->id, '5', "0", STR_PAD_LEFT);

                            $catalogo = Catalogo::find($catalogoId);

                            $url = 'secure.payco.co';//$this->getRequest()->getHttpHost();
                            $url2 = '/apprest';

                            $rutaqr = $url . $url2 . '/images/QRcodes/' . $txtcodigo . '.png';
                            $urltxtcodigo = "https://default.epayco.me/catalogo/{$catalogo->nombre}/{$catalogueProductReference->id}";

                            $url_qr = getenv("BASE_URL_REST")."/".getenv("BASE_URL_APP_REST_ENTORNO").CommonText::PATH_TEXT_CODE . $urltxtcodigo;


                            $this->sendCurlVariables($url_qr, [], "GET", true);

                            $catalogueProductReference->txtcodigo = $txtcodigo;
                            $catalogueProductReference->rutaqr = $rutaqr;
                            $catalogueProductReference->save();
                        } else {
                            $catalogueProductReference = CatalogoProductosReferencias::where("id", $productReferences[$position]["id"])
                                ->where("estado", 1)
                                ->where("id_catalogo_productos", $cobro->id)->first();
                        }

                        if (!$catalogueProductReference) {
                            $success = false;
                            $title_response = 'Error';
                            $text_response = "Reference not exist'";
                            $last_action = 'query database ';
                            $error = "Error update product";
                            $data = [];

                            $arr_respuesta['success'] = $success;
                            $arr_respuesta['titleResponse'] = $title_response;
                            $arr_respuesta['textResponse'] = $text_response;
                            $arr_respuesta['lastAction'] = $last_action;
                            $arr_respuesta['data'] = $data;

                            return $arr_respuesta;
                        }


                        $reference = time();

                        $catalogueProductReference->nombre = $productReferences[$position]["name"];
                        $catalogueProductReference->valor = $productReferences[$position]["amount"];
                        $catalogueProductReference->cantidad = $productReferences[$position]["quantity"];
                        $catalogueProductReference->disponible = $productReferences[$position]["quantity"];
                        $catalogueProductReference->estado = true;
                        $catalogueProductReference->fecha_creacion = new \DateTime("now");
                        $catalogueProductReference->id_catalogo_productos = $cobro->id;
                        $catalogueProductReference->cobrounico = $onePayment;
                        $catalogueProductReference->descripcion = $description;
                        $catalogueProductReference->base_iva = isset($base) ? $base : 0;
                        $catalogueProductReference->moneda = $currency;
                        $catalogueProductReference->tipocobro = 1;
                        $catalogueProductReference->url_confirmacion = $urlConfirmation;
                        $catalogueProductReference->url_respuesta = $urlResponse;
                        $catalogueProductReference->iva = $tax;
                        $catalogueProductReference->numerofactura = $reference;
                        $catalogueProductReference->fecha_expiracion = $expirationDate;
                        $catalogueProductReference->rutaqr = "";

                        $txtcodigo = str_pad($cobro->id, '5', "0", STR_PAD_LEFT);

                        $catalogo = Catalogo::find($catalogoId);

                        $url = 'secure.payco.co';//$this->getRequest()->getHttpHost();
                        $url2 = '/apprest';

                        $rutaqr = $url . $url2 . '/images/QRcodes/' . $txtcodigo . '.png';
                        $urltxtcodigo = "https://default.epayco.me/catalogo/{$catalogo->nombre}/{$catalogueProductReference->id}";

                        $url_qr = getenv("BASE_URL_REST")."/".getenv("BASE_URL_APP_REST_ENTORNO").CommonText::PATH_TEXT_CODE . $urltxtcodigo;


                        $this->sendCurlVariables($url_qr, [], "GET", true);

                        $catalogueProductReference->txtcodigo = $txtcodigo;
                        $catalogueProductReference->rutaqr = $rutaqr;
                        $catalogueProductReference->save();


                        $img = isset($productReferences[$position]["img"]) ? $productReferences[$position]["img"] : null;
                        if ($img) {
                            if (is_object($img)) {
                                $img = (array)$img;;
                            }
                            $totalImg = count($img);
                            if ($totalImg > 10) {
                                $success = false;
                                $title_response = 'number of files exceeded';
                                $text_response = 'number of files exceeded';
                                $last_action = 'number of files exceeded';
                                $data = [];
                                $arr_respuesta['success'] = $success;
                                $arr_respuesta['titleResponse'] = $title_response;
                                $arr_respuesta['textResponse'] = $text_response;
                                $arr_respuesta['lastAction'] = $last_action;
                                $arr_respuesta['data'] = $data;

                                return $arr_respuesta;
                            }
                            if ($totalImg > 0) {
                                for ($k = 0; $k < $totalImg; $k++) {
                                    $data = explode(',', $img[$k]);
                                    $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');
                                    $sacarExt = explode('image/', $data[0]);
                                    $sacarExt = explode(';', $sacarExt[1]);

                                    if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
                                        $success = false;
                                        $title_response = 'file format not allowed';
                                        $text_response = 'file format not allowed';
                                        $last_action = 'file format not allowed';
                                        $data = [];
                                        $arr_respuesta['success'] = $success;
                                        $arr_respuesta['titleResponse'] = $title_response;
                                        $arr_respuesta['textResponse'] = $text_response;
                                        $arr_respuesta['lastAction'] = $last_action;
                                        $arr_respuesta['data'] = $data;
                                    }
                                    $base64 = base64_decode($data[1]);
                                    file_put_contents(
                                        $tmpfname . "." . $sacarExt[0],
                                        $base64
                                    );


                                    $fechaActual = new \DateTime('now');


                                    //Subir los archivos
                                    $nameFile = "{$clientId}_cobros_files_{$fechaActual->getTimestamp()}.{$sacarExt[0]}";
                                    $urlFile = "catalogo/files/{$nameFile}";

                                    $catalogueProductReferenceFile = CatalogoProductosReferenciasFiles::where("id_catalogo_productos_referencias", $catalogueProductReference->id)
                                        ->where("posicion", $k)
                                        ->where("estado", 1)
                                        ->first();
                                    if (!$catalogueProductReferenceFile) $catalogueProductReferenceFile = new CatalogoProductosReferenciasFiles();
                                    $catalogueProductReferenceFile->url = $urlFile;
                                    $catalogueProductReferenceFile->id_catalogo_productos_referencias = $catalogueProductReference->id;
                                    $catalogueProductReferenceFile->posicion = $k;
                                    $catalogueProductReferenceFile->estado = true;
                                    $catalogueProductReferenceFile->save();

                                    //Subir a rackespace
                                    $this->uploadFile('media', $tmpfname . "." . $sacarExt[0], $nameFile, 'catalogo/files');

                                    unlink($tmpfname . "." . $sacarExt[0]);
                                }
                            }
                            for ($j = $totalImg + 1; $j <= 10; $j++) {
                                $catalogueProductReferenceFile = CatalogoProductosReferenciasFiles::where("id_catalogo_productos_referencias", $catalogueProductReference->id)
                                    ->where("posicion", $j)
                                    ->where("estado", 1)
                                    ->first();
                                if ($catalogueProductReferenceFile) {
                                    $catalogueProductReferenceFile->estado = 0;
                                    $catalogueProductReferenceFile->save();
                                } else {
                                    break;
                                }
                            }

                            $imgs = CatalogoProductosReferenciasFiles::where("id_catalogo_productos_referencias", $catalogueProductReference->id)
                                ->where("estado", 1)->get()->toArray();
                            $imgReturn = [];
                            foreach ($imgs as $key => $img) {
                                $imgReturn[] = array(
                                    "id" => $img["id"],
                                    "url" => "https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/" . $img["url"]
                                );
                            }
                        }

                        $productReferencesArray[] = [
                            "id" => $catalogueProductReference->id,
                            "title" => $catalogueProductReference->nombre,
                            "quantity" => $catalogueProductReference->cantidad,
                            "available" => $catalogueProductReference->disponible,
                            "amount" => $catalogueProductReference->valor,

                            "date" => (new \DateTime($catalogueProductReference->fecha))->format("Y-m-d H:i:s"),
                            "txtCode" => $catalogueProductReference->txtcodigo,
                            "baseTax" => $catalogueProductReference->base_iva,
                            "description" => $catalogueProductReference->descripcion,
                            "currency" => $catalogueProductReference->moneda,
                            "urlConfirmation" => $catalogueProductReference->url_confirmacion,
                            "urlResponse" => $catalogueProductReference->url_respuesta,
                            "tax" => $catalogueProductReference->iva,
                            "invoiceNumber" => $catalogueProductReference->numerofactura,
                            "expirationDate" => $catalogueProductReference->fecha_expiracion,
                            "routeQr" => $url_qr,
                            "routeLink" => $urltxtcodigo,

                            "img" => isset($imgReturn) ? $imgReturn : [],

                        ];
                    } else {
                        break;
                    }


                }
            }
//            else {
//                $catalogueProductReference = CatalogoProductosReferencias::
//                where("id_catalogo_productos", $cobro->id)->get()->toArray();
//
//                if ($catalogueProductReference) {
//                    $catalogueProductReferenceInsert = CatalogoProductosReferencias::find($catalogueProductReference[0]["id"]);
//                } else {
//                    $catalogueProductReferenceInsert = new CatalogoProductosReferencias();
//                }
//
//                /**
//                 * @var $cobro CatalogoProductos
//                 */
//                $reference = time();
//                $catalogueProductReferenceInsert->nombre = $cobro->titulo;
//                $catalogueProductReferenceInsert->valor = $cobro->valor;
//                $catalogueProductReferenceInsert->cantidad = $cobro->cantidad;
//                $catalogueProductReferenceInsert->disponible = $cobro->disponible;
//                $catalogueProductReferenceInsert->estado = true;
//                $catalogueProductReferenceInsert->fecha_creacion = new \DateTime("now");
//                $catalogueProductReferenceInsert->id_catalogo_productos = $cobro->id;
//                $catalogueProductReferenceInsert->cobrounico = $onePayment;
//                $catalogueProductReferenceInsert->descripcion = $description;
//                $catalogueProductReferenceInsert->base_iva = isset($base) ? $base : 0;
//                $catalogueProductReferenceInsert->moneda = $currency;
//                $catalogueProductReferenceInsert->tipocobro = 1;
//                $catalogueProductReferenceInsert->url_confirmacion = $urlConfirmation;
//                $catalogueProductReferenceInsert->url_respuesta = $urlResponse;
//                $catalogueProductReferenceInsert->iva = $tax;
//                $catalogueProductReferenceInsert->numerofactura = $reference;
//                $catalogueProductReferenceInsert->fecha_expiracion = $expirationDate;
//                $catalogueProductReferenceInsert->rutaqr = "";
//
//                $txtcodigo = str_pad($cobro->id, '5', "0", STR_PAD_LEFT);
//
//                $catalogo = Catalogo::find($catalogoId);
//
//                $url = 'secure.payco.co';//$this->getRequest()->getHttpHost();
//                $url2 = '/apprest';
//
//                $rutaqr = $url . $url2 . '/images/QRcodes/' . $txtcodigo . '.png';
//                $urltxtcodigo = "https://default.epayco.me/catalogo/{$catalogo->nombre}/{$catalogueProductReferenceInsert->id}";
//
//                $url_qr = "https://secure2.epayco.io/apprest/printqr?txtcodigo=" . $urltxtcodigo;
//
//
//                $code = $this->sendCurlVariables($url_qr, [], "GET", true);
//
//                $catalogueProductReferenceInsert->txtcodigo = $txtcodigo;
//                $catalogueProductReferenceInsert->rutaqr = $rutaqr;
//
//                $catalogueProductReferenceInsert->save();
//
//                if ($catalogueProductReference) {
//                    for ($m = 1; $m < count($catalogueProductReference); $m++) {
//                        $catalogueProductReference[$m] = CatalogoProductosReferencias::find($catalogueProductReference[$m]["id"]);
//                        $catalogueProductReference[$m]->estado = false;
//                        $catalogueProductReference[$m]->save();
//                    }
//                }
//
//                $productReferencesArray[] = [
//                    "id" => $catalogueProductReferenceInsert->id,
//                    "title" => $catalogueProductReferenceInsert->nombre,
//                    "quantity" => $catalogueProductReferenceInsert->cantidad,
//                    "available" => $catalogueProductReferenceInsert->disponible,
//                    "amount" => $catalogueProductReferenceInsert->valor,
//
//                    "date" => (new \DateTime($catalogueProductReferenceInsert->fecha))->format("Y-m-d H:i:s"),
//                    "txtCode" => $catalogueProductReferenceInsert->txtcodigo,
//                    "clientId" => $catalogueProductReferenceInsert->cliente_id,
//                    "baseTax" => $catalogueProductReferenceInsert->base_iva,
//                    "description" => $catalogueProductReferenceInsert->descripcion,
//                    "currency" => $catalogueProductReferenceInsert->moneda,
//                    "urlConfirmation" => $catalogueProductReferenceInsert->url_confirmacion,
//                    "urlResponse" => $catalogueProductReferenceInsert->url_respuesta,
//                    "tax" => $catalogueProductReferenceInsert->iva,
//                    "invoiceNumber" => $catalogueProductReferenceInsert->numerofactura,
//                    "expirationDate" => $catalogueProductReferenceInsert->fecha_expiracion,
//                    "routeQr" => $url_qr,
//                    "routeLink" => $urltxtcodigo,
//
//                    "img" => isset($imgReturn) ? $imgReturn : [],
//
//                ];
//
//
//            }


            if (count($categorias) > 0) {

                //Borramos todas las categorias de ese cliente
                CatalogoProductosCategorias::where('catalogo_producto_id', $cobro->id)->delete();

                foreach ($categorias as $key => $id) {

                    $existeCategoriaProducto = CatalogoProductosCategorias::where('catalogo_producto_id', $cobro->id)->where('catalogo_categoria_id', $id)->get()->first();

                    $existeCategoria = CatalogoCategorias::find($id);

                    if (!$existeCategoriaProducto && $existeCategoria) {

                        $catalogo_categorias = new CatalogoProductosCategorias();
                        $catalogo_categorias->catalogo_categoria_id = $id;
                        $catalogo_categorias->catalogo_producto_id = $cobro->id;
                        $catalogo_categorias->save();
                    }
                }

            }

            if ($nueva_categoria != "") {

                //Buscamos la nueva categoria
                $existe = CatalogoCategorias::where('catalogo_id', $cobro->catalogo_id)->where('nombre', $nueva_categoria)->get()->first();

                if (!$existe) {

                    $categoria = new CatalogoCategorias();
                    $categoria->catalogo_id = $catalogoId;
                    $categoria->fecha = date("Y-m-d H:i:s");
                    $categoria->nombre = ucwords(strtolower($nueva_categoria));
                    $categoria->save();

                    $catalogo_categorias = new CatalogoProductosCategorias();
                    $catalogo_categorias->catalogo_categoria_id = $categoria->id;
                    $catalogo_categorias->catalogo_producto_id = $cobro->id;
                    $catalogo_categorias->save();

                }

            }

            //Consultamos categorias del producto

            $categorias_producto = CatalogoProductosCategorias::where('catalogo_producto_id', $cobro->id)->get();

            $arr_categorias = array();

            if (count($categorias_producto) > 0) {
                foreach ($categorias_producto as $categoria)
                    $arr_categorias[] = $categoria->catalogo_categoria_id;
            }

//            if ($img) {
//                if(is_object($img)){
//                    $newImg[0]=$img;
//                    $img=$newImg;
//                }
//                $totalImg = count($img);
//                if ($totalImg > 0) {
//                    for ($k = 0; $k < $totalImg; $k++) {
//                        $fechaActual = new \DateTime('now');
//                        $name = $img[$k]->getClientOriginalName();
//                        $tmp_name = $img[$k]->getPathname();
//                        $fileTipe = $img[$k]->getClientOriginalExtension();
//
//                        //Subir los archivos
//                        $nameFile = "{$clientId}_cobros_files_{$fechaActual->getTimestamp()}.{$fileTipe}";
//                        $urlFile = "cobros/files/{$nameFile}";
//
//                        $filecobro = new FilesCobro();
//                        $filecobro->fechacreacion=$fechaActual;
//                        $filecobro->nombre=$name;
//                        $filecobro->tipo=1;
//                        $filecobro->url=$urlFile;
//                        $filecobro->cobro_id=$cobro->id;
//                        $filecobro->save();
//
//                        //Subir a rackespace
//                        $this->uploadDocumentosLegales($nameFile, $tmp_name);
//                    }
//                }
//            }
//                $doc = $document;
//                if ($doc) {
//                    $fechaActual = new \DateTime('now');
//                    $name = $doc->getClientOriginalName();
//                    $tmp_name = $doc->getPathname();
//                    $fileTipe = $doc->getClientOriginalExtension();
//                    if ($tmp_name != "") {
//                        //Subir los archivos
//                        $nameFile = "{$clientId}_cobros_files_{$fechaActual->getTimestamp()}.{$fileTipe}";
//                        $urlFile = "cobros/files/{$nameFile}";
//
//                        $filecobro = new Filescobro();
//                        $filecobro->fechacreacion=$fechaActual;
//                        $filecobro->nombre=$name;
//                        $filecobro->tipo=2;
//                        $filecobro->url=$urlFile;
//                        $filecobro->cobro_id=$cobro->id;
//                        $filecobro->save();
//
//                        //Subir a rackespace
//                        $this->uploadDocumentosLegales($nameFile, $tmp_name);
//                    }
//                }
            $txtcodigo = str_pad($cobro->id, '5', "0", STR_PAD_LEFT);

            $catalogo = Catalogo::find($catalogoId);

            $url = 'secure.payco.co';//$this->getRequest()->getHttpHost();
            $url2 = '/apprest';

            $rutaqr = $url . $url2 . '/images/QRcodes/' . $txtcodigo . '.png';
            $urltxtcodigo = "https://default.epayco.me/catalogo/{$catalogo->nombre}/{$cobro->id}";

            $url_qr = getenv("BASE_URL_REST")."/".getenv("BASE_URL_APP_REST_ENTORNO").CommonText::PATH_TEXT_CODE . $urltxtcodigo;


            $this->sendCurlVariables($url_qr, [], "GET", true);

            $cobro->txtcodigo = $txtcodigo;
            $cobro->rutaqr = $rutaqr;
            $cobro->save();


            $newData = [
                "date" => $cobro->fecha->format("Y-m-d H:i:s"),
                "state" => $cobro->estado,
                "txtCode" => $cobro->txtcodigo,
                "clientId" => $cobro->cliente_id,
                "quantity" => $cobro->cantidad,
                "baseTax" => $cobro->base_iva,
                "description" => $cobro->descripcion,
                "title" => $cobro->titulo,
                "currency" => $cobro->moneda,
                "urlConfirmation" => $cobro->url_confirmacion,
                "urlResponse" => $cobro->url_respuesta,
                "tax" => $cobro->iva,
                "amount" => $cobro->valor,
                "invoiceNumber" => $cobro->numerofactura,
                "expirationDate" => $cobro->fecha_expiracion,
                "contactName" => $cobro->nombre_contacto,
                "contactNumber" => $cobro->numero_contacto,
                "routeQr" => $url_qr,
                "routeLink" => $urltxtcodigo,
                "id" => $cobro->id,
                "img" => isset($imagenes) ? $imagenes : [],
                "shippingTypes" => $tiposEnviosArray,
                "categories" => $arr_categorias,
                "references" => $productReferencesArray,

            ];
            $success = true;
            $title_response = 'Successful consult';
            $text_response = 'successful consult';
            $last_action = 'successful consult';
            $data = $newData;

        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error create new product";
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
}