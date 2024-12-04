<?php
namespace App\Service\V2\Product\Process;

use App\Exceptions\GeneralException;
use App\Helpers\Messages\CommonText;
use App\Helpers\ClientS3;
use App\Helpers\Pago\HelperPago;
use App\Helpers\Validation\CommonValidation;
use App\Http\Validation\Validate as Validate;
use App\Repositories\V2\CatalogueRepository;
use App\Repositories\V2\ClientRepository;
use App\Helpers\Validation\ValidateUrlImage;
use App\Repositories\V2\ProductRepository;
use App\Repositories\V2\ShoppingCartRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreateProductService extends HelperPago
{
    protected CatalogueRepository $catalogueRepository;
    protected ProductRepository $productRepository;
    protected ShoppingCartRepository $shoppingCartRepository;
    protected ClientRepository $clientRepository;

    public Request $rq;

    public function __construct(CatalogueRepository $catalogueRepository, ProductRepository $productRepository, ShoppingCartRepository $shoppingCartRepository, ClientRepository $clientRepository)
    {
        $this->catalogueRepository = $catalogueRepository;
        $this->productRepository = $productRepository;
        $this->shoppingCartRepository = $shoppingCartRepository;
        $this->clientRepository = $clientRepository;
    }

    public function process($data, $request)
    {
        $this->rq = $request;
        try {
            list($clientId, $title, $invoiceNumber, $description, $amount, $currency, $tax, $base, $discountPrice, $onePayment, $quantity, $available, $expirationDate, $urlResponse, $urlConfirmation, $catalogoId, $nombreContacto, $numeroContacto, $sales, $img, $arr_categorias, $origin, $discountPrice, $outstanding, $discountRate, $active, $activeTax, $activeConsumptionTax, $consumptionTax, $netAmount, $epaycoDeliveryProvider, $epaycoDeliveryProviderValues, $realWeight, $high, $long, $width, $declaredValue) = $this->validationDefined($data);
            list($tiposEnviosArray, $tiposEnviosArrayResponse) = $this->validationShipping($data);
            list($setupReferences, $productReferences, $showInventory) = $this->validationReferences($data);

            $this->validatePrices($discountPrice, $amount);

            $catalogueResult = $this->catalogueRepository->find($catalogoId);
            if (!isset($catalogueResult) || $catalogueResult === null) {
                $success = false;
                $title_response = 'CatalogueId not found';
                $text_response = 'CatalogueId is null';
                $last_action = 'create new product';
                $data = [];
                $arr_respuesta['success'] = $success;
                $arr_respuesta['titleResponse'] = $title_response;
                $arr_respuesta['textResponse'] = $text_response;
                $arr_respuesta['lastAction'] = $last_action;
                $arr_respuesta['data'] = $data;

                return $arr_respuesta;
            }

            list($idProd, $txtcodigo) = CommonValidation::validationId($data);
            list($update, $aux, $productEdataStateBefore) = $this->validateUpdate($idProd);

            $catalogueData = $catalogueResult;
            $nameCatalogue = $catalogueData->nombre;

            $path = $this->getPathByOrigin($nameCatalogue, $clientId);
            $urltxtcodigo = $path . '/product-info/' . $arr_categorias . '/' . $idProd;

            $url_qr = getenv("BASE_URL_REST") . "/" . getenv("BASE_URL_APP_REST_ENTORNO") . CommonText::PATH_TEXT_CODE . $urltxtcodigo;

            //valido imagenes
            list($arrImg) = $this->validateImage($img, $clientId, $update, $aux);

            //referencias de un producto
            //sumar referencia
            if ($productReferences) {
                list(
                    $sumRef,
                    $productReferencesArray,
                    $productReferencesArrayResponse
                ) = $this->mappingRefEpayco(
                    $origin,
                    $idProd,
                    $clientId,
                    $productReferences,
                    $aux,
                    $nameCatalogue,
                    $description,
                    $invoiceNumber,
                    $urlResponse,
                    $expirationDate,
                    $base,
                    $urlConfirmation,
                    $tax,
                    $currency,
                    $url_qr
                );
            } else {
                $sumRef = 0;
                $productReferencesArray = [];
                $productReferencesArrayResponse = [];
            }
            $setupReferencesArray = $this->mappingSetupReferences($setupReferences);

            $apifyClient = $this->getAlliedEntity($clientId);
            $newData = [
                "id" => intval($idProd),
                "cliente_id" => $clientId,
                "fecha" => date("c"),
                "fecha_actualizacion" => date("c"),
                "txtcodigo" => $txtcodigo,
                "ruta_qr" => $url_qr,
                'route_link' => $urltxtcodigo,
                "titulo" => $title,
                "numerofactura" => $invoiceNumber,
                "descripcion" => $description,
                "valor" => $amount,
                "moneda" => $currency,
                "iva" => $tax,
                "base_iva" => $base,
                "precio_descuento" => $discountPrice,
                "cobrounico" => $onePayment,
                "cantidad" => CommonValidation::ternaryHelper($sumRef > 0, $sumRef, $quantity),
                "disponible" => CommonValidation::ternaryHelper($sumRef > 0, $sumRef, $available),
                "estado" => 1,
                "fecha_expiracion" => $expirationDate != null ? $expirationDate->getTimestamp() : null,
                "url_respuesta" => $urlResponse,
                "url_confirmacion" => $urlConfirmation,
                "tipocobro" => 1,
                "catalogo_id" => $catalogoId,
                "nombre_contacto" => $nombreContacto,
                "numero_contacto" => $numeroContacto,
                "ventas" => $sales,
                "img" => (array) $arrImg,
                "envio" => (array) $tiposEnviosArray,
                "categorias" => array_map('intval', (array) $arr_categorias),
                "referencias" => (array) $productReferencesArray,
                CommonText::ENTIDAD_ALIADA => $apifyClient["alliedEntityId"],
                CommonText::FECHA_CREACION_CLIENTE => $apifyClient["clientCreatedAt"],
            ];

            $this->addEpaycoProperties($newData, [
                "discountPrice" => $discountPrice,
                "showInventory" => $showInventory,
                "outstanding" => $outstanding,
                "discountRate" => $discountRate,
                "active" => $active,
                "activeTax" => $activeTax,
                "activeConsumptionTax" => $activeConsumptionTax,
                "consumptionTax" => $consumptionTax,
                "netAmount" => $netAmount,
                "epaycoDeliveryProvider" => $epaycoDeliveryProvider,
                "epaycoDeliveryProviderValues" => $epaycoDeliveryProviderValues,
                "realWeight" => $realWeight,
                "high" => $high,
                "long" => $long,
                "width" => $width,
                "declaredValue" => $declaredValue,
                "setupReferences" => $setupReferencesArray,
            ]);

            //para la respuesta en ingles
            $newResponse = [
                'id' => intval($newData['id']),
                'clientId' => $newData['cliente_id'],
                'date' => $newData['fecha'],
                'txtCode' => $newData['txtcodigo'],
                'routeQr' => $newData['ruta_qr'],
                'routeLink' => $newData['route_link'],
                'title' => $newData['titulo'],
                'invoiceNumber' => $newData['numerofactura'],
                'description' => $newData['descripcion'],
                'amount' => $newData['valor'],
                'currency' => $newData['moneda'],
                'tax' => $newData['iva'],
                'baseTax' => $newData['base_iva'],
                'available' => $newData['disponible'],
                'quantity' => $newData['cantidad'],
                'state' => $newData['estado'],
                'expirationDate' => $newData['fecha_expiracion'],
                'urlResponse' => $newData['url_respuesta'],
                'urlConfirmation' => $newData['url_confirmacion'],
                'contactName' => $newData['nombre_contacto'],
                'contactNumber' => $newData['numero_contacto'],
                'img' => (array) $arrImg,
                'shippingTypes' => $tiposEnviosArrayResponse,
                'categories' => array_map('intval', $newData['categorias']),
                'references' => $productReferencesArrayResponse,
            ];

            $this->addEpaycoResponseProperties($newResponse, $newData);

            if ($update) {

                unset($newData["ventas"]);
                $updateProductoResponse = $this->productRepository->update(intval($newData['id']), $newData);
               
                if ($updateProductoResponse) {
                    $success = true;
                    $title_response = 'Successful update product';
                    $text_response = 'successful update product';
                    $last_action = 'update product';
                    $data = $newResponse;

                    $this->deleteCatalogueRedis($catalogoId);
                } else {
                    $success = false;
                    $title_response = 'Error update product';
                    $text_response = 'error update product';
                    $last_action = 'update product';
                    $data = [];
                }
            } else {


                $result = $this->productRepository->create($newData);

                if ($result) {

                    $success = true;
                    $title_response = 'Successful consult';
                    $text_response = 'successful consult';
                    $last_action = 'successful consult';
                    $data = $newResponse;

                    $this->deleteCatalogueRedis($catalogoId);
                } else {
                    $success = false;
                    $title_response = 'Error create product';
                    $text_response = 'error create product';
                    $last_action = 'create product';
                    $data = [];
                }
            }

        } catch (GeneralException $e) {

            $success = false;
            $title_response = $e->getMessage();
            $text_response = $e->getMessage();
            $last_action = 'Create new product';
            $data = $e->getData();

            $arr_respuesta['success'] = $success;
            $arr_respuesta['titleResponse'] = $title_response;
            $arr_respuesta['textResponse'] = $text_response;
            $arr_respuesta['lastAction'] = $last_action;
    
            $arr_respuesta['data'] = $data;
    
            return $arr_respuesta;
        } catch (\Exception $exception) {
            Log::info($exception);

            $success = false;
            $title_response = 'Error ';
            $text_response = "Error create new product ";
            $last_action = 'fetch data from database ';
            $error = (object) $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalerrores' => $validate->totalerrors,
                'errores' =>
                $validate->errorMessage,
            );
        }

        $arr_respuesta['success'] = $success;
        $arr_respuesta['titleResponse'] = $title_response;
        $arr_respuesta['textResponse'] = $text_response;
        $arr_respuesta['lastAction'] = $last_action;

        $arr_respuesta['data'] = $data;

        return $arr_respuesta;
    }
    public function validationDefined($data): array
    {
        $clientId = $data["clientId"];
        $title = CommonValidation::getFieldValidation($data, "title", null);
        $invoiceNumber = CommonValidation::getFieldValidation($data, "invoiceNumber", null);
        $description = CommonValidation::getFieldValidation($data, "description", null);
        $amount = CommonValidation::getFieldValidation($data, "amount", 0);
        $currency = CommonValidation::getFieldValidation($data, "currency", null);
        $tax = isset($data["tax"]) && $data["tax"] !== "" ? $data["tax"] : 0;
        $base = CommonValidation::getFieldValidation($data, "baseTax", null);
        $onePayment = CommonValidation::getFieldValidation($data, "onePayment", 1);
        $quantity = CommonValidation::getFieldValidation($data, "quantity", null);
        $availableString = "available";
        $available = CommonValidation::getFieldValidation($data, $availableString, 0);
        $expirationDate = CommonValidation::getFieldValidation($data, "expirationDate", null);
        $urlResponse = CommonValidation::getFieldValidation($data, "urlResponse", null);
        $urlConfirmation = CommonValidation::getFieldValidation($data, "urlConfirmation", null);
        $catalogoId = CommonValidation::getFieldValidation($data, "catalogueId", null);

        $nombreContacto = CommonValidation::getFieldValidation($data, "contactName", null);

        $numeroContacto = CommonValidation::getPhoneNumber($data);
        $sales = CommonValidation::getFieldValidation($data, "sales", 0);
        $img = CommonValidation::getFieldValidation($data, "img", []);
        $arr_categorias = CommonValidation::getFieldValidation($data, "categories", []);
        $origin = CommonValidation::getFieldValidation($data, "origin");
        $discountPrice = CommonValidation::getFieldValidation($data, "discountPrice", 0);
        $discountRate = CommonValidation::getFieldValidation($data, "discountRate", 0);
        $outstanding = CommonValidation::getFieldValidation($data, "outstanding", false);
        $active = CommonValidation::getFieldValidation($data, CommonText::ACTIVE_ENG, true);
        $activeTax = CommonValidation::getFieldValidation($data, "activeTax", false);
        $activeConsumptionTax = CommonValidation::getFieldValidation($data, "activeConsumptionTax", false);
        $consumptionTax = CommonValidation::getFieldValidation($data, "consumptionTax", 0);
        $netAmount = CommonValidation::getFieldValidation($data, "netAmount", 0);
        $epaycoDeliveryProvider = CommonValidation::getFieldValidation($data, "epaycoDeliveryProvider", false);
        $epaycoDeliveryProviderValues = CommonValidation::getFieldValidation($data, "epaycoDeliveryProviderValues", []);
        $realWeight = CommonValidation::getFieldValidation($data, "realWeight", 0);
        $high = CommonValidation::getFieldValidation($data, "high", 0);
        $long = CommonValidation::getFieldValidation($data, "long", 0);
        $width = CommonValidation::getFieldValidation($data, "width", 0);
        $declaredValue = CommonValidation::getFieldValidation($data, "declaredValue", 0);
        return array($clientId, $title, $invoiceNumber, $description, $amount, $currency, $tax, $base, $discountPrice, $onePayment, $quantity, $available, $expirationDate, $urlResponse, $urlConfirmation, $catalogoId, $nombreContacto, $numeroContacto, $sales, $img, $arr_categorias, $origin, $discountPrice, $outstanding, $discountRate, $active, $activeTax, $activeConsumptionTax, $consumptionTax, $netAmount, $epaycoDeliveryProvider, $epaycoDeliveryProviderValues, $realWeight, $high, $long, $width, $declaredValue);
    }
    public function validationShipping($fieldValidation): array
    {
        $tiposEnviosArray = [];
        $tiposEnviosArrayResponse = [];
        if (isset($fieldValidation["shippingTypes"]) && count($fieldValidation["shippingTypes"]) > 0) {
            foreach ($fieldValidation["shippingTypes"] as $key => $value) {
                $tiposEnviosArray[$key]["tipo"] = $value["type"];
                $tiposEnviosArray[$key]["valor"] = $value["amount"];
                $tiposEnviosArrayResponse[$key]["type"] = $value["type"];
                $tiposEnviosArrayResponse[$key]["amount"] = $value["amount"];
            }
        }

        return array($tiposEnviosArray, $tiposEnviosArrayResponse);
    }
    public function validationReferences($fieldValidation): array
    {
        $setupReferences = CommonValidation::getFieldValidation($fieldValidation, "setupReferences", []);
        $productReferences = CommonValidation::getFieldValidation($fieldValidation, "productReferences", []);
        $showInventory = CommonValidation::getFieldValidation($fieldValidation, "showInventory", false);
        return array($setupReferences, $productReferences, $showInventory);
    }
    private function validatePrices($discountPrice, $amount)
    {
        if ($discountPrice > $amount) {
            throw new GeneralException("Discount price must be less an amount", [['codError' => 500, 'errorMessage' => 'Discount price must be less an amount']]);
        }
    }

    public function validateUpdate($idProd): array
    {

        //aux obtener cantidad de la referencia
        $aux = [];
        $productEdataStateBefore = null;
        $products = $this->productRepository->find($idProd);

        $update = false;
        if (isset($products) && isset($products['referencias'])) {
            $update = true;
            foreach ($products['referencias'] as $key => $value) {
                $aux[$key]['id'] = $value['id'];
                $aux[$key]['cantidad'] = $value['cantidad'];
            }
        }
        if (isset($products) && isset($products['img']) && count($products['img']) > 0) {
            foreach ($products['img'] as $key => $value) {
                $aux['img'][$key] = $value;
            }
        }
        if (isset($products)) {
            $productEdataStateBefore = $products['edata_estado'];
        }

        return array($update, $aux, $productEdataStateBefore);

    }

    private function getPathByOrigin($catalogueName, $clientId)
    {
        //Subdominio
        $clientSubdomainSearch = $this->clientRepository->find($clientId);

        $clientSubdomain = isset($clientSubdomainSearch->url) ? $clientSubdomainSearch->url : "";
        $path = $clientSubdomain . "/vende/" . $catalogueName;
        return $path;
    }

    public function validateImage($img, $clientId, $update = false, $aux = [])
    {
        $arrImg = [];
        $deleteImages = [];
        $this->validateImgIsObjectAndImgQuantity($img);
        for ($k = 0; $k < count($img); $k++) {
            if (!is_array($img[$k]) && substr($img[$k], 0, 5) == 'https') {
                $img[$k] = substr($img[$k], strlen(ValidateUrlImage::getDomainFromUrl($img[$k])) + 1);
            } else if (!is_array($img[$k]) && strpos(trim($img[$k]), ValidateUrlImage::getDomainFromUrl($img[$k]) . '/') === 0) {
                array_push($deleteImages, $img[$k]);
            } else if (is_array($img[$k]) && count($img[$k]) === 1 && substr($img[$k][0], 0, 5) == 'https') {
                $img[$k] = substr($img[$k][0], strlen(ValidateUrlImage::getDomainFromUrl($img[$k][0])) + 1);
            } else if (is_array($img[$k]) && count($img[$k]) > 1) {
                $img[$k] = $img[$k][1];
            }
            $data = explode(',', $img[$k]);

            if (count($data) > 1) {

                $tmpfname = tempnam(sys_get_temp_dir(), 'archivo');

                $sacarExt = $this->getImgExtension($data);

                $base64 = base64_decode($data[1]);

                //Subir los archivos
                $token = random_int(100, 999);
                $fechaActual = new \DateTime('now');
                $nameFile = "{$clientId}_{$fechaActual->getTimestamp()}_{$token}.{$sacarExt[0]}";
                $urlFile = "vende/productos/{$nameFile}";

                file_put_contents(
                    $tmpfname . "." . $sacarExt[0],
                    $base64
                );
                $bucketName = getenv("AWS_BUCKET_MULTIMEDIA_EPAYCO");

                $clientS3 = new ClientS3();
                $clientS3->uploadFileAws($bucketName, $tmpfname . "." . $sacarExt[0], $urlFile);
                $arrImg[] = $urlFile;
                unlink($tmpfname . "." . $sacarExt[0]);

            } else {
                $arrImg[] = $data[0];
            }
        }

        if ($update && isset($aux['img']) && count($aux['img']) > 0) {
            $this->updateImagesPathEpayco($arrImg, $aux['img']);
        }
        return $arrImg = array($arrImg);
    }
    private function validateImgIsObjectAndImgQuantity($img)
    {

        $maxImg = 5;
        if (is_object($img)) {
            $img = (array) $img;
        }
        if (count($img) > $maxImg) {
            throw new GeneralException(CommonText::FILES_EXEEDED, [['codError' => 500, 'errorMessage' => CommonText::FILES_EXEEDED]]);
        }
        return $img;

    }
    private function getImgExtension($data)
    {
        $sacarExt = explode('image/', $data[0]);
        if (count($sacarExt) > 1) {
            $sacarExt = explode(';', $sacarExt[1]);
            if ($sacarExt[0] != "jpg" && $sacarExt[0] != "jpeg" && $sacarExt[0] !== "png") {
                throw new GeneralException(CommonText::FORMAT_NOT_ALLOWED, [['codError' => 500, 'errorMessage' => CommonText::FORMAT_NOT_ALLOWED]]);
            }
        } else {
            throw new GeneralException("Invalid base 64", [['codError' => 500, 'errorMessage' => 'Invalid base 64']]);
        }

        return $sacarExt;
    }

    private function updateImagesPathEpayco(&$arrImg, $aux)
    {
        foreach ($arrImg as $key => $newImg) {
            foreach ($aux as $currentImgKey => $currentImg) {
                if ($key == $currentImgKey) {
                    if ($newImg == "delete") {
                        $arrImg[$key] = "";
                    } else if ($newImg == "" || (strpos($newImg, 'https') === 0)) {
                        $arrImg[$key] = $this->validatePath($newImg);
                    } else {
                        $arrImg[$key] = $newImg;
                    }
                }
            }
        }
    }
    private function validatePath($url)
    {
        $paths = explode(ValidateUrlImage::getDomainFromUrl($url) . '/', trim($url));
        if (count($paths) > 1) {
            return $paths[1];
        }
        return $url;
    }

    public function mappingRefEpayco(
        $origin,
        $idProd,
        $clientId,
        $productReferences,
        $aux,
        $nameCatalogue,
        $description,
        $invoiceNumber,
        $urlResponse,
        $expirationDate,
        $base,
        $urlConfirmation,
        $tax,
        $currency,
        $url_qr
    ): array {
        $sumRef = 0;
        $productReferencesArray = [];
        $productReferencesArrayResponse = [];
        $this->validateReferencesParams($productReferences);
        $maxRef = 50;
        for ($i = 1; $i <= $maxRef; $i++) {
            $position = $i - 1;
            if (isset($productReferences[$position])) {

                $idProdReferencia = $this->generateObjectId();

                $txtcodigoRef = str_pad($idProd, '5', "0", STR_PAD_LEFT);

                list($quantityUpdate) = $this->validateCalculateQuantityReference(
                    $productReferences,
                    $position
                );

                $arrImgReference = $this->validateReferencesHelper(
                    $productReferences,
                    $position,
                    $clientId,
                    $nameCatalogue,
                    $description
                );

                $productReferencesArray[] = [
                    "descripcion" => $description,
                    "numerofactura" => $invoiceNumber,
                    "url_respuesta" => $urlResponse,
                    "valor" => CommonValidation::ternaryHelper(
                        isset($productReferences[$position]["amount"]),
                        $productReferences[$position]["amount"],
                        null
                    ),
                    "fecha_expiracion" => $expirationDate,
                    "nombre" => CommonValidation::ternaryHelper(
                        isset($productReferences[$position]["name"]),
                        $productReferences[$position]["name"],
                        null
                    ),
                    "base_iva" => (!$base) ? $base : 0,
                    "fecha" => isset($productReferences[$position]["fecha"]) ?
                    (new \DateTime($productReferences[$position]["fecha"]))->format("Y-m-d H:i:s")
                    : null,
                    "url_confirmacion" => $urlConfirmation,
                    "route_link" => "",
                    "txtcodigo" => $txtcodigoRef,
                    "iva" => $tax,
                    "moneda" => $currency,
                    "id" => $idProdReferencia,
                    "rutaqr" => "",
                    "cantidad" => (int) $quantityUpdate,
                    "disponible" => (int) CommonValidation::ternaryHelper(
                        isset($productReferences[$position]["quantity"]),
                        $productReferences[$position]["quantity"],
                        0
                    ),
                    "img" => count($arrImgReference) > 0 ? $arrImgReference[0] : "",

                ];
                $this->addEpaycoProperties(
                    $productReferencesArray[$position],
                    $productReferences[$position],
                    true
                );

                foreach ($productReferencesArray as $key => $value) {
                    $productReferencesArrayResponse[] = [
                        "description" => $value['descripcion'],
                        "invoiceNumber" => $value['numerofactura'],
                        "urlResponse" => $value['url_respuesta'],
                        "amount" => $value['valor'],
                        "expirationDate" => $value['fecha_expiracion'],
                        "name" => $value['nombre'],
                        "baseTax" => $value['base_iva'],
                        "date" => $value['fecha'],
                        "urlConfirmation" => $value['url_confirmacion'],
                        "routeLink" => $value['route_link'],
                        "txtCode" => $value['txtcodigo'],
                        "tax" => $value['iva'],
                        "currency" => $value['moneda'],
                        "id" => $value['id'],
                        "routeQr" => $value['rutaqr'],
                        "quantity" => $value['cantidad'],
                        "available" => $value['disponible'],
                        "img" => $value['img'],

                    ];

                    $this->addEpaycoResponseProperties($productReferencesArrayResponse[$key], $value, true);

                }

                if (isset($productReferences[$position]["quantity"])) {
                    $sumRef = $sumRef + $productReferences[$position]["quantity"];
                }

            } else {
                break;
            }
        }
        return array($sumRef, $productReferencesArray, $productReferencesArrayResponse);

    }


    private function generateObjectId() {
        $timestamp = pack('N', time());
    
        $random = random_bytes(5);
    
        static $counter = 0;
    
        $counter = ($counter + 1) % 0xFFFFFF;
    
        $counterBytes = pack('C*', 
            ($counter >> 16) & 0xFF,  
            ($counter >> 8) & 0xFF,   
            $counter & 0xFF            
        );
    
        return bin2hex($timestamp . $random . $counterBytes);
    }
    private function validateReferencesParams($productReferences)
    {
        $maxRef = 50;
        for ($i = 1; $i <= $maxRef; $i++) {
            $position = "reference" . $i;
            $invalidPositionText = "Invalid " . $position;
            if (isset($productReferences[$position])) {
                if (isset($productReferences[$position]["amount"]) && isset($productReferences[$position]["name"])) {
                    $name = $productReferences[$position]["name"];
                    $amount = $productReferences[$position]["amount"];

                    if (strlen($name) < 1 || strlen($name) > 50) {
                        throw new GeneralException($invalidPositionText . " field name invalid", [['codError' => 500, 'errorMessage' => 'field name invalid']]);
                    }

                    if ((float) $amount == 0) {
                        throw new GeneralException($invalidPositionText . " field amount invalid", [['codError' => 500, 'errorMessage' => 'field amount invalid']]);
                    }
                } else {
                    throw new GeneralException($invalidPositionText . " field amount or name is required", [['codError' => 500, 'errorMessage' => 'field amount or name is required']]);
                }

            } else {
                break;
            }
        }
    }

    public function validateCalculateQuantityReference($productReferences, $position): array
    {
        $quantityUpdate = isset($productReferences[$position]["quantity"]) ? $productReferences[$position]["quantity"] : 0;
        return array($quantityUpdate);
    }

    public function validateReferencesHelper($productReferences, $position, $clientId, $nameCatalogue, $description)
    {

        $arrImgReference = isset($productReferences[$position]["img"]) ? $this->validateImage([$productReferences[$position]["img"]], $clientId, $nameCatalogue)[0] : [];
        $arrImgReference = CommonValidation::ternaryHelper(empty($arrImgReference), [""], $arrImgReference);
        return $arrImgReference;
    }

    private function addEpaycoProperties(&$newData, $epaycoProperties, $isResference = false)
    {

        if (!$isResference) {
            if ($epaycoProperties["discountPrice"] > 0 && ($epaycoProperties["discountRate"] < 0 || $epaycoProperties["discountRate"] > 100)) {
                throw new GeneralException("Discount rate invalid", [['codError' => 500, 'errorMessage' => 'Discount rate invalid']]);
            }
            $newData["configuraciones_referencias"] = $epaycoProperties["setupReferences"];
            $newData["porcentaje_descuento"] = $epaycoProperties["discountRate"];
            $newData["mostrar_inventario"] = $epaycoProperties["showInventory"];
            $newData["origen"] = "epayco";
            $newData["destacado"] = $epaycoProperties["outstanding"];
            $newData["activo"] = $this->getProductIsActive($epaycoProperties["active"]);
            $newData["iva_activo"] = $epaycoProperties["activeTax"];
            $newData["ipoconsumo_activo"] = $epaycoProperties["activeConsumptionTax"];
            $newData["ipoconsumo"] = $epaycoProperties["consumptionTax"];
            $newData["monto_neto"] = $epaycoProperties["netAmount"];
            $newData[CommonText::EPAYCO_LOGISTIC] = $epaycoProperties["epaycoDeliveryProvider"];
            $newData[CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES] = $epaycoProperties["epaycoDeliveryProviderValues"];
            $newData[CommonText::REAL_WEIGHT] = $epaycoProperties["realWeight"];
            $newData[CommonText::HIGH] = $epaycoProperties["high"];
            $newData[CommonText::LONG] = $epaycoProperties["long"];
            $newData[CommonText::WIDTH] = $epaycoProperties["width"];
            $newData[CommonText::DECLARED_VALUE] = $epaycoProperties["declaredValue"];
        } else {
            $newData["iva"] = $epaycoProperties["tax"];
            $newData["ipoconsumo"] = $epaycoProperties["consumptionTax"];
            $newData["porcentaje_descuento"] = $epaycoProperties["discountRate"];
            $newData["precio_descuento"] = $epaycoProperties["discountPrice"];
            $newData["monto_neto"] = $epaycoProperties["netAmount"];
        }
    }

    private function getProductIsActive($active)
    {

        return $active;
    }

    private function addEpaycoResponseProperties(&$newResponse, &$newData, $isRef = false)
    {
        if (!$isRef) {
            $newResponse["discountRate"] = $newData["porcentaje_descuento"];
            $newResponse["discountPrice"] = $newData["precio_descuento"];
            $newResponse["showInventory"] = $newData["mostrar_inventario"];
            $newResponse["setupReferences"] = $newData["configuraciones_referencias"];
            $newResponse["origin"] = $newData["origen"];
            $newResponse["outstanding"] = $newData["destacado"];
            $newResponse["activeConsumptionTax"] = $newData["ipoconsumo_activo"];
            $newResponse["consumptionTax"] = $newData["ipoconsumo"];
            $newResponse["activeTax"] = $newData["iva_activo"];
            $newResponse[CommonText::ACTIVE_ENG] = $this->getProductIsActive($newData["activo"]);
            $newResponse["netAmount"] = $newData["monto_neto"];
            $newResponse["epaycoDeliveryProvider"] = $newData[CommonText::EPAYCO_LOGISTIC];
            $newResponse["epaycoDeliveryProviderValues"] = $newData[CommonText::EPAYCO_DELIVERY_PROVIDER_VALUES];
            $newResponse["realWeight"] = $newData[CommonText::REAL_WEIGHT];
            $newResponse["high"] = $newData[CommonText::HIGH];
            $newResponse["long"] = $newData[CommonText::LONG];
            $newResponse["width"] = $newData[CommonText::WIDTH];
            $newResponse["declaredValue"] = $newData[CommonText::DECLARED_VALUE];
        } else {
            $newResponse["discountRate"] = $newData["porcentaje_descuento"];
            $newResponse["discountPrice"] = $newData["precio_descuento"];
            $newResponse["netAmount"] = $newData["monto_neto"];
            $newResponse["consumptionTax"] = $newData["ipoconsumo"];
            $newResponse["tax"] = $newData["iva"];
        }
    }

    private function mappingSetupReferences($setupReferences)
    {
        $setupReferencesArray = [];
        foreach ($setupReferences as $value) {
            $item["tipo"] = $value["type"];
            $item["nombre"] = $value["name"];
            $item["valores"] = $value["values"];
            array_push($setupReferencesArray, $item);
        }
        return $setupReferencesArray;
    }

    private function deleteCatalogueRedis($catalogueId)
    {
        $redis = app('redis')->connection();
        $exist = $redis->exists('vende_catalogue_' . $catalogueId);
        if ($exist) {
            $redis->del('vende_catalogue_' . $catalogueId);
        }
    }

}
