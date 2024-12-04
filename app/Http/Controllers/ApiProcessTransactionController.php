<?php

namespace App\Http\Controllers;

use App\Events\MongoTransaction\Process\ProcessCreateMongoTransactionEvent;
use App\Events\MongoTransaction\Validation\ValidationDataMongoTransactionEvent;
use App\Events\Payments\Process\ProcessConfirmTransactionStandardEvent;
use App\Events\Payments\Process\ProcessTransactionStandardEvent;
use App\Events\Payments\Process\ProcessTransactionTcEvent;
use App\Events\Payments\Process\ProcessTransactionCashEvent;
use App\Events\Payments\Process\ProcessTransactionDPEvent;
use App\Events\Payments\Process\ProcessConfirmTransactionDPEvent;
use App\Events\Payments\Process\ProcessTransactionSafetypayEvent;
use App\Events\Payments\Validation\ValidationConfirmTransactionStandardEvent;
use App\Events\Payments\Validation\ValidationTransactionStandardEvent;
use App\Events\Payments\Validation\ValidationTransactionTcEvent;
use App\Events\Payments\Validation\ValidationTransactionDPEvent;
use App\Events\Payments\Validation\ValidationConfirmTransactionDPEvent;
use App\Events\Payments\Validation\ValidationTransactionCashEvent;
use App\Events\Payments\Validation\ValidationTransactionSafetypayEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ApiProcessTransactionController extends HelperPago
{

    public function __construct(Request $request)
    {
        parent::__construct($request);

    }

    public function setTransactionTc(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralTransactionTc = event(
                new ValidationTransactionTcEvent($arr_parametros),
                $request);

            if (!$validationGeneralTransactionTc[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTransactionTc[0]);
            }

            $transactionsTc = event(
                new ProcessTransactionTcEvent($validationGeneralTransactionTc[0]),
                $request
            );
            $success = $transactionsTc[0]['success'];
            $title_response = $transactionsTc[0]['titleResponse'];
            $text_response = $transactionsTc[0]['textResponse'];
            $last_action = $transactionsTc[0]['lastAction'];
            $data = $transactionsTc[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function getPseBanks(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $test = isset($request->test) ? $request->test == "true" : null; 
            $pseBanks = $this->getPseBanksSdk($arr_parametros["clientId"], $test);

            if (!$pseBanks) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error getBank pse';
                $arrResponse['textResponse'] = 'Error getBank pse';
                $arrResponse['lastAction'] = 'get_bank_pse';
                $arrResponse['data'] = ["error" => "Error valid transaction"];
                return $arrResponse;
            }
            if (!$pseBanks->success) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error getBank pse' . isset($pseBanks->title_response) ? $pseBanks->title_response : "";
                $arrResponse['textResponse'] = "Error getBank pse" . isset($pseBanks->text_response) ? $pseBanks->text_response : "";
                $arrResponse['lastAction'] = 'get_bank_pse';
                $arrResponse['data'] = ["error" => isset($pseBanks->data) ? $pseBanks->data : []];
                return $arrResponse;
            }

            $success = true;
            $title_response = $pseBanks->title_response;
            $text_response = $pseBanks->text_response;
            $last_action = $pseBanks->last_action;
            $data = $pseBanks->data;

        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }


    public function setTransactionStandard(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralTransactionStandard = event(
                new ValidationTransactionStandardEvent($arr_parametros),
                $request);

            if (!$validationGeneralTransactionStandard[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTransactionStandard[0]);
            }

            $transactionsStandard = event(
                new ProcessTransactionStandardEvent($validationGeneralTransactionStandard[0]),
                $request
            );
            $success = $transactionsStandard[0]['success'];
            $title_response = $transactionsStandard[0]['titleResponse'];
            $text_response = $transactionsStandard[0]['textResponse'];
            $last_action = $transactionsStandard[0]['lastAction'];
            $data = $transactionsStandard[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function confirmTransactionStandard(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralConfirmTransactionStandard = event(
                new ValidationConfirmTransactionStandardEvent($arr_parametros),
                $request);

            if (!$validationGeneralConfirmTransactionStandard[0]["success"]) {
                return $this->crearRespuesta($validationGeneralConfirmTransactionStandard[0]);
            }

            $confirmTransactionsStandard = event(
                new ProcessConfirmTransactionStandardEvent($validationGeneralConfirmTransactionStandard[0]),
                $request
            );
            $success = $confirmTransactionsStandard[0]['success'];
            $title_response = $confirmTransactionsStandard[0]['titleResponse'];
            $text_response = $confirmTransactionsStandard[0]['textResponse'];
            $last_action = $confirmTransactionsStandard[0]['lastAction'];
            $data = $confirmTransactionsStandard[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function getEntitiesCash(Request $request)
    {
        try {
            $data = $this->getEntities();

            if (!$data) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error get entities cash';
                $arrResponse['textResponse'] = 'Error get entities cash';
                $arrResponse['lastAction'] = 'get_entities_cash';
                $arrResponse['data'] = ["error" => "Error valid transaction"];
                return $arrResponse;
            }

            $success = true;
            $title_response = 'entities for cash';
            $text_response = 'entities for cash';
            $last_action = 'get_entities_cash';
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function setTransactionCash(Request $request)
    {
        try {
            $params = $request->request->all();

            $validationGeneralTransactionCash = event(
                new ValidationTransactionCashEvent($params),
                $request);

            if (!$validationGeneralTransactionCash[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTransactionCash[0]);
            }

            $transactionsCash = event(
                new ProcessTransactionCashEvent($validationGeneralTransactionCash[0]),
                $request
            );
            $success = $transactionsCash[0]['success'];
            $title_response = $transactionsCash[0]['titleResponse'];
            $text_response = $transactionsCash[0]['textResponse'];
            $last_action = $transactionsCash[0]['lastAction'];
            $data = $transactionsCash[0]['data'];
        } catch (\Exception $exception) {

            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function getTransaction(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $transactionID = isset($arr_parametros["referencePayco"]) ? $arr_parametros["referencePayco"] : $request->get("referencePayco", null);
            if (!$transactionID) {
                $arrResponse['success'] = false;
                $arrResponse['titleResponse'] = 'Error';
                $arrResponse['textResponse'] = 'Some fields are required, please correct the errors and try again';
                $arrResponse['lastAction'] = 'validation_data';
                $arrResponse['data'] = ["errors" => ["codError" => 500, "errorMessage" => "field referencePayco required"]];
                return $arrResponse;
            }

            $transaction = $this->getAllTransaction($arr_parametros["clientId"], $transactionID);

            if (!$transaction) {
               $success = false;
               $title_response = 'Error referencePayco';
               $text_response = 'Error referencePayco';
               $last_action = 'get_transaction';
               $data = ["error" => "Error valid transaction"];
            }else {
                $success = $transaction->success ?? false;
                $title_response = $transaction->title_response ?? "Error transaction";
                $text_response = $transaction->text_response ?? "Error transaction";
                $last_action = $transaction->last_action ?? 'get_transaction';
                $data = $success ? ["transaction" => $this->translateTransaction($transaction->data)] : ["error" => $this->translateTransaction($transaction->data ?? [] ) ];
            }
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function setTransactionDP(Request $request) // DP = daviplata
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralTransactionDP = event(
                new ValidationTransactionDPEvent($arr_parametros),
                $request);

            if (!$validationGeneralTransactionDP[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTransactionDP[0]);
            }

            $transactionsDaviplata = event(
                new ProcessTransactionDPEvent($validationGeneralTransactionDP[0]),
                $request
            );
            $success = $transactionsDaviplata[0]['success'];
            $title_response = $transactionsDaviplata[0]['titleResponse'];
            $text_response = $transactionsDaviplata[0]['textResponse'];
            $last_action = $transactionsDaviplata[0]['lastAction'];
            $data = $transactionsDaviplata[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function confirmTransactionDP(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationConfirmTransactionDP = event(
                new ValidationConfirmTransactionDPEvent($arr_parametros),
                $request);

            if (!$validationConfirmTransactionDP[0]["success"]) {
                return $this->crearRespuesta($validationConfirmTransactionDP[0]);
            }

            $confirmTransactionsDP = event(
                new ProcessConfirmTransactionDPEvent($validationConfirmTransactionDP[0]),
                $request
            );
            $success = $confirmTransactionsDP[0]['success'];
            $title_response = $confirmTransactionsDP[0]['titleResponse'];
            $text_response = $confirmTransactionsDP[0]['textResponse'];
            $last_action = $confirmTransactionsDP[0]['lastAction'];
            $data = $confirmTransactionsDP[0]['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function setTransactionSafetypay(Request $request)
    {
        try {
            $arr_parametros = $request->request->all();
            $validationGeneralTransactionSafetypay = event(
                new ValidationTransactionSafetypayEvent($arr_parametros),
                $request);
            if (!$validationGeneralTransactionSafetypay[0]["success"]) {
                return $this->crearRespuesta($validationGeneralTransactionSafetypay[0]);
            }
            $transactions = event(
                new ProcessTransactionSafetypayEvent($validationGeneralTransactionSafetypay[0]),
                $request
            );
            $success = $transactions[0]['success'];
            $title_response = $transactions[0]['titleResponse'];
            $text_response = $transactions[0]['textResponse'];
            $last_action = $transactions[0]['lastAction'];
            $data = $transactions[0]['data'];
        } catch (\Exception $exception) {

            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $last_action = "NA";
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        $response = array(
            'success' => $success,
            'titleResponse' => $title_response,
            'textResponse' => $text_response,
            'lastAction' => $last_action,
            'data' => $data
        );
        return $this->crearRespuesta($response);
    }

    public function createMongoTransaction(Request $request)
    {
        try {
            $last_action = "NA";
            $arr_parametros = $request->request->all();
            $validationRequest = event(
                new ValidationDataMongoTransactionEvent($arr_parametros),
                $request)[0];
            $last_action = "validar_data_mongo";
            if (!$validationRequest["success"]) {
                return $this->crearRespuesta($validationRequest);
            }
            $transaction = event(
                new ProcessCreateMongoTransactionEvent($validationRequest),
                $request
            )[0];
            $last_action = "procesar_data_mongo";
            $success = $transaction['success'];
            $title_response = $transaction['titleResponse'];
            $text_response = $transaction['textResponse'];
            $last_action = $transaction['lastAction'];
            $data = $transaction['data'];
        } catch (\Exception $exception) {
            $success = false;
            $title_response = "Error code: " . $exception->getCode();
            $text_response = "Error internal server: " . $exception->getMessage();
            $error = $this->getErrorCheckout('AE100');
            $validate = new Validate();
            $validate->setError($error->error_code, $error->error_message);
            $data = array(
                'totalErrors' => $validate->totalerrors,
                'errors' => $validate->errorMessage
            );
        }
        return $this->defaultApiResponse($success, $title_response, $text_response, $last_action, $data);
    }
}