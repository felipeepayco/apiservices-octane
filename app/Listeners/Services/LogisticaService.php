<?php


namespace App\Listeners\Services;
use Illuminate\Http\Request;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use App\Helpers\Pago\HelperPago;

use App\Helpers\Messages\CommonText as CM;

class LogisticaService extends HelperPago
{

  public function __construct()
  {
      parent::__construct(new Request());
  }

  public function handleGuideGeneration($shoppingCartData, $operator, $catalogue, $epaycoReference, $quote, $serviceId, &$guide, $nota, $pagWeb){
    $defaultReturt = ["status" => true];
    if ($quote !== null) {
        $arryNames = explode(" ", $shoppingCartData->envio->nombre);
        $firstName2 = "-";
        $lastName = "-";
        $lastName2 = "";
        if (count($arryNames) > 2) {
            $firstName2 = $arryNames[1];
            $lastName = $arryNames[2];
            $lastName2 = $arryNames[3];
        }
        if (count($arryNames) === 2) {
            $lastName = $arryNames[1];
        }
        list($weight, $declaredValue, $shoppingProducts) = $this->handleCalcProducts($shoppingCartData->productos);
        $time = time();
        $bodyGuide = [
            "operador" => $operator,
            "id_configuracion" => $catalogue->configuracion_recogida_id,
            "id_servicio" => $serviceId,
            "fecha_recogida" => date("Y-m-d"),
            "razon_social_destinatario" => "-",
            "tipo_identificacion_destinatario" => "CC",
            "identificacion_destinatario" => "000",
            "primer_nombre_destinatario" => $arryNames[0],
            "segundo_nombre_destinatario" => $firstName2,
            "primer_apellido_destinatario" => $lastName,
            "segundo_apellido_destinatario" => $lastName2,
            "telefono_destinatario" => strval($shoppingCartData->envio->telefono),
            "direccion_destinatario" => explode("/",$shoppingCartData->envio->direccion)[0],
            "ciudad_destinatario" => strval($shoppingCartData->envio->codigo_dane),
            "cantidad_unidades" => "1",
            "peso_volumen" => ceil(($quote->empaquetado_sugerido->alto *  $quote->empaquetado_sugerido->ancho *  $quote->empaquetado_sugerido->largo)/ 2500),
            "valor_mercancia" => $declaredValue,
            "peso_real" => ceil($weight),
            "alto" => ceil($quote->empaquetado_sugerido->alto),
            "largo" => ceil($quote->empaquetado_sugerido->largo),
            "ancho" => ceil($quote->empaquetado_sugerido->ancho),
            "ref_payco" => strval($epaycoReference),
            "observaciones" => $nota
        ];
        $response = $this->elogisticaRequest($bodyGuide, "/api/v1/guia");
        if (isset($response['success']) && $response['success'] && isset($response['data']->guia)) {
            $response["fecha_registro"] = date("d/m/Y h:i:s a", $time);
            $guide[$operator] = $response;
            $datosemail = [
                "subject" => "notificación envio de guía",
                "to" => $shoppingCartData->envio->correo,
                "name" => $arryNames[0]." ". $lastName,
                "guide" =>  $guide[$operator]['data']->guia,
                "date" => date("Y-m-d"),
                "address" => explode("/",$shoppingCartData->envio->direccion)[0],
                "provider" => $operator,
                "email" => $catalogue->correo_contacto,
                "web" => $pagWeb,
                "telephone" => $catalogue->telefono_remitente,
                "name_business" => $catalogue->nombre_empresa,
                "product" => $shoppingProducts,
            ];
            $this->sendEmailGuia($datosemail);
        } else if (isset($response['debug'])) {
            return [
                "status" => false,
                "debug" => $response['debug']
            ];
        } else {
          $defaultReturt= ["status" => false];
        }
    } else {
        $guide[$operator] = null;
    }
    return $defaultReturt;
  }

  public function sendEmailGuia($data) {
      $baseUrlRest = getenv("BASE_URL_REST");
      $pathPanelAppRest = getenv("BASE_URL_APP_REST_ENTORNO");
      $url = "{$baseUrlRest}/{$pathPanelAppRest}/email/guia/logistica";
      return $this->apiService($url, $data, "POST");
  }

  public function handleCalcProducts($products){
    $searchProducts = new Search();
    $searchProducts->setSize(500);
    $searchProducts->setFrom(0);

    $boolSearchProducts = new BoolQuery();

    foreach($products as $product){
        $boolSearchProducts->add(new TermQuery("id", $product->id),BoolQuery::SHOULD);
    }

    $searchProducts->addQuery($boolSearchProducts);
    $productsResult = $this->consultElasticSearch($searchProducts->toArray(), "producto", false);

    $shoppingProducts = $productsResult["data"];
    $weight = 0;
    $declaredValue = 0;
    for ($i=0; $i < count($shoppingProducts); $i++) { 
        $weight = $weight + $shoppingProducts[$i]->peso_real;
        $declaredValue = $declaredValue + $shoppingProducts[$i]->valor_declarado;
        $shoppingProducts[$i]->cantidad = $products[$i]->cantidad;
    }
    return [$weight, $declaredValue, $shoppingProducts];
  }

}
