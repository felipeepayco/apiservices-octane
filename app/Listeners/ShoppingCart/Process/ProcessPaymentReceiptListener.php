<?php

namespace App\Listeners\ShoppingCart\Process;


use App\Events\ShoppingCart\Process\ProcessPaymentReceiptEvent;
use App\Helpers\Edata\HelperEdata;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;


use App\Models\Clientes;
use Illuminate\Http\Request;

use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use PDF;

class ProcessPaymentReceiptListener extends HelperPago
{

    /**
     * CatalogueNewListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    public function explodeAuthorization($authorization){

        $explodeAuthorization = explode("-",$authorization);
        
        $authorizationNumber = isset($explodeAuthorization[0])?$explodeAuthorization[0]:"";
        $originNumber = isset($explodeAuthorization[1])?$explodeAuthorization[1]:"";

        return ["authorization"=>$authorizationNumber,"originNumber"=>$originNumber];

    }

    /**
     * @param CatalogueNewEvent $event
     * @return mixed
     * @throws \Exception
     */
    public function handle(ProcessPaymentReceiptEvent $event)
    {
        try {
            $fieldValidation = $event->arr_parametros;
            $clientId = $fieldValidation["clientId"];
            $id = $fieldValidation["id"];
            
            $clientInfo = Clientes::select("celular","tipo_doc","documento","nombre_empresa","tipo_documentos.codigo","email")
                ->join('tipo_documentos', 'clientes.tipo_doc', '=', 'tipo_documentos.id')
                ->where("clientes.id",$clientId)->first();
            
            $businessName = strtoupper($clientInfo->nombre_empresa);
          
            //Obtener listado de transacciones en base a las fechas seleccionadas 
            $searchTransaction = new Search();
            $searchTransaction->setSize(1);
            $searchTransaction->setFrom(0);

            $searchTransaction->addQuery(new MatchQuery('id_cliente', $clientId), BoolQuery::FILTER);
            $searchTransaction->addQuery(new MatchQuery('estado', 'Aceptada'), BoolQuery::FILTER);
            $searchTransaction->addQuery(new MatchQuery('id_factura', $id), BoolQuery::FILTER);
            
            $transactionResult = $this->consultElasticSearch($searchTransaction->toArray(), "transacciones_rest", false);

            $searchShoppingCart = new Search();
            $searchShoppingCart->setSize(1);
            $searchShoppingCart->setFrom(0);

            $searchShoppingCart->addQuery(new MatchQuery('clienteId', $clientId), BoolQuery::FILTER);
            $searchShoppingCart->addQuery(new MatchQuery('id', $id), BoolQuery::FILTER);
            
            $shopppingCartResult = $this->consultElasticSearch($searchShoppingCart->toArray(), "shoppingcart", false);
            
            if(count($transactionResult["data"]) > 0 && count($shopppingCartResult["data"]) > 0){  

                $transaction = $transactionResult["data"][0];
                $shoppingCart = $shopppingCartResult["data"][0];

                $authorizationExplode = $this->explodeAuthorization($transaction->autorizacion);
                
                $transactionDate = date("d/m/Y H:m",$transaction->fecha);
                $clientMobileNumber = $transaction->extras->extra5;
                $daviplataOrigin = "****".substr($authorizationExplode["originNumber"],-4);
                $authorizationNumber = $authorizationExplode["authorization"];
                $ip = $transaction->ip_transaccion;
                $reason = "Compra";
                $shippingName = $transaction->nombres." ".$transaction->apellidos;
                $shippingPhone = $transaction->telefono;
                $shippingAddress = $transaction->direccion;
                $shippingCity = $transaction->ciudad;
                $shippingAmount = number_format((float)$transaction->extras->extra2, 2, '.',',');
                $transactionAmont = number_format((float)($transaction->extras->extra1+$transaction->extras->extra2), 2, '.',',');
                $transactionCost = number_format((float)0, 2, '.',',');
                $products = [];

                foreach ($shoppingCart->productos as $product){
                    if(isset($product->referencias)){
                        foreach($product->referencias as $reference){
                            $receiptProduct = [
                                "title"=>$product->titulo." - ".$reference->titulo,
                                "quantity"=>$reference->cantidad,
                                "amount"=>number_format(($reference->valor*$reference->cantidad), 2, '.',',')
                            ];
                            array_push($products,$receiptProduct);
                        }
                    }else{
                        $receiptProduct = [
                            "title"=>$product->titulo,
                            "quantity"=>$product->cantidad,
                            "amount"=>number_format(($product->valor*$product->cantidad), 2, '.',',')
                        ];
                        array_push($products,$receiptProduct);
                    }                  
                }

                $paymentReceiptData =[
                    "transactionDate"=>$transactionDate,
                    "clientMobileNumber"=>$clientMobileNumber,
                    "authorizationNumber"=>$authorizationNumber,
                    "ip"=>$ip,
                    "reason"=>$reason,
                    "shippingName"=>$shippingName,
                    "shippingPhone"=>$shippingPhone,
                    "shippingAddress"=>$shippingAddress,
                    "shippingCity"=>$shippingCity,
                    "shippingAmount"=>$shippingAmount,
                    "transactionAmont"=>$transactionAmont,
                    "transactionCost"=>$transactionCost,
                    "products"=>$products,
                    "businessName"=>$businessName,
                    "daviplataOrigin"=>$daviplataOrigin,
                    "transactionState"=> "APROBADA"
                ];

                $pdf = app('dompdf.wrapper');
                $pdf->getDomPDF()->set_option("enable_php", true);
                $pdf->loadView('template_pdf_proof_of_payment_dp.index', compact('paymentReceiptData'));

                $nameFile = "payment_receipt".date('YmdHis').'.pdf';
                $route = storage_path('app');
                $dir_file = $route . "/" . $nameFile;
                $pdf->save($dir_file);

                $pdfExport = file_get_contents($dir_file);
                $pdf_base64 = base64_encode($pdfExport);
                unlink($dir_file);

                $success = true;
                $title_response = "Generated sales movements";
                $text_response = "Generated sales movements ";
                $last_action = "generated_sales_movements";
                $data = ["file"=>"data:application/pdf;base64,".$pdf_base64];
            }else{
                $success = false;
                $title_response = "Transaction does not exist";
                $text_response = "Transaction does not exist ";
                $last_action = "generated_payment_receipt";
                $data = [];
            }
        } catch (Exception $exception) {
            $success = false;
            $title_response = 'Error';
            $text_response = "Error create new catalogue";
            $last_action = 'fetch data from database';
            $error = $this->getErrorCheckout('E0100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array('totalErrors' => $validate->totalerrors, 'errors' =>
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
