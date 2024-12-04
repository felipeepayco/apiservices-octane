<?php

namespace App\Listeners\Payments\Validation;

use App\Events\Payments\Validation\ValidationTokenCustomerEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\Validate;
use Illuminate\Http\Request;

class ValidationTokenCustomerListener extends HelperPago
{

    /**
     * ValidationTokenCustomerListener constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {

        parent::__construct($request);
    }

    /**
     * @param ValidationTokenCustomerEvent $event
     * @return array
     */
    public function handle(ValidationTokenCustomerEvent $event)
    {
        $validate = new Validate();
        $data = $event->arr_parametros;

        ///Validar parametros obligatorios ///////////////////////////////////////////////
        if (isset($data['clientId'])) {
            $clientId = $data['clientId'];
        } else {
            $clientId = false;
        }

        ///// clientId /////////////////
        if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(500, "field clientId required");
            } else {
                $arrResponse['clientId'] = $clientId;
            }
        } else {
            $validate->setError(500, "field clientId required");
        }
        ///// clientId /////////////////

        $requireCardToken = true;
        if (isset($data['requireCardToken'])) {
            if (is_bool($data['requireCardToken'])) {
                $requireCardToken = $data['requireCardToken'];
            }else{
                $validate->setError(500, "field requireCardToken is type boolean");
            }
        }
        $arrResponse['requireCardToken'] = (boolean)$requireCardToken;


        if($requireCardToken) {
            //// cardTokenId ///////////
            if (isset($data['cardTokenId'])) {
                $cardTokenId = $data['cardTokenId'];
            } else {
                $cardTokenId = false;
            }
            if (isset($cardTokenId)) {
                $vcardTokenId = $validate->ValidateVacio($cardTokenId, 'cardTokenId');
                if (!$vcardTokenId) {
                    $validate->setError(500, "field cardTokenId required");
                } else {
                    if (is_string($cardTokenId)) {
                        $arrResponse['cardTokenId'] = (string)$cardTokenId;
                    } else {
                        $validate->setError(500, "field cardTokenId is type string");
                    }
                }
            } else {
                $validate->setError(500, "field cardTokenId required");
            }
            ///// cardTokenId ///////////
        }

        ///// docType ///////////
        if (isset($data['docType'])) {
            $docType = $data['docType'];
        } else {
            $docType = false;
        }
        if (isset($docType)) {
            $vdocType = $validate->ValidateVacio($docType, 'docType');
            if (!$vdocType) {
                $validate->setError(500, "field docType required");
            } else {
                if (is_string($docType)) {
                    $arrResponse['docType'] = (string)$docType;
                } else {
                    $validate->setError(500, "field docType is type string");
                }
            }
        } else {
            $validate->setError(500, "field docType required");
        }
        ///// docType ///////////

        ///// docNumber ///////////
        if (isset($data['docNumber'])) {

            $docNumber = $data['docNumber'];
        } else {
            $docNumber = false;
        }
        if (isset($docNumber)) {
            $vdocNumber = $validate->ValidateVacio($docNumber, 'docNumber');
            if (!$vdocNumber) {
                $validate->setError(500, "field docNumber required");
            } else {
                if (is_string($docNumber)) {
                    $arrResponse['docNumber'] = (string)$docNumber;
                } else {
                    $validate->setError(500, "field docNumber is type string");
                }
            }
        } else {
            $validate->setError(500, "field docNumber required");
        }
        ///// docNumber ///////////

        ///// name ///////////
        if (isset($data['name'])) {
            $name = $data['name'];
        } else {
            $name = false;
        }
        if (isset($name)) {
            $vname = $validate->ValidateVacio($name, 'name');
            if (!$vname) {
                $validate->setError(500, "field name required");
            } else {
                if (is_string($name)) {
                    $arrResponse['name'] = (string)$name;
                } else {
                    $validate->setError(500, "field name is type string");
                }
            }
        } else {
            $validate->setError(500, "field name required");
        }
        ///// name ///////////

        //// lastName ///////////
        if (isset($data['lastName'])) {
            $lastName = $data['lastName'];
        } else {
            $lastName = false;
        }
        if (isset($lastName)) {
            $vlastName = $validate->ValidateVacio($lastName, 'lastName');
            if (!$vlastName) {
                $validate->setError(500, "field lastName required");
            } else {
                if (is_string($lastName)) {
                    $arrResponse['lastName'] = (string)$lastName;
                } else {
                    $validate->setError(500, "field lastName is type string");
                }
            }
        } else {
            $validate->setError(500, "field lastName required");
        }
        ///// lastName ///////////

        //// email ///////////
        if (isset($data['email'])) {
            $email = $data['email'];
        } else {
            $email = false;
        }
        if (isset($email)) {
            $vemail = $validate->ValidateVacio($email, 'email');
            if (!$vemail) {
                $validate->setError(500, "field email required");
            } else {
                if (is_string($email)) {
                    $arrResponse['email'] = (string)$email;
                } else {
                    $validate->setError(500, "field email is type string");
                }
            }
        } else {
            $validate->setError(500, "field email required");
        }
        ///// email ///////////

        //// cellPhone ///////////
        if (isset($data['cellPhone'])) {
            $cellPhone = $data['cellPhone'];
        } else {
            $cellPhone = false;
        }
        if (isset($cellPhone)) {
            $vcellPhone = $validate->ValidateVacio($cellPhone, 'cellPhone');
            if (!$vcellPhone) {
                $validate->setError(500, "field cellPhone required");
            } else {
                if (is_string($cellPhone)) {
                    $arrResponse['cellPhone'] = (string)$cellPhone;
                } else {
                    $validate->setError(500, "field cellPhone is type string");
                }
            }
        } else {
            $validate->setError(500, "field cellPhone required");
        }
        ///// cellPhone ///////////

        //// phone ///////////
        if (isset($data['phone'])) {
            $phone = $data['phone'];
        } else {
            $phone = false;
        }
        if (isset($phone)) {
            $vphone = $validate->ValidateVacio($phone, 'phone');
            if (!$vphone) {
                $validate->setError(500, "field phone required");
            } else {
                if (is_string($phone)) {
                    $arrResponse['phone'] = (string)$phone;
                } else {
                    $validate->setError(500, "field phone is type string");
                }
            }
        } else {
            $validate->setError(500, "field phone required");
        }
        ///// phone ///////////


        ///////////  address /////////////////
        $address = "";
        if (isset($data["address"])) {
            if (is_string($data["address"])) {
                $address = (string)$data["address"];
            } else {
                $validate->setError(500, "field address is type string");
            }
        }
        $arrResponse["address"] = $address;
        ///////////  address /////////////////

        ///////////  city /////////////////
        $city = "";
        if (isset($data["city"])) {
            if (is_string($data["city"])) {
                $city = (string)$data["city"];
            } else {
                $validate->setError(500, "field city is type string");
            }
        }
        $arrResponse["city"] = $city;
        ///////////  city /////////////////


        if ($validate->totalerrors > 0) {
            $success = false;
            $lastAction = 'validation data';
            $titleResponse = 'Error';
            $textResponse = 'Some fields are required, please correct the errors and try again';

            $data =
                array('totalErrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage);
            $response = array(
                'success' => $success,
                'titleResponse' => $titleResponse,
                'textResponse' => $textResponse,
                'lastAction' => $lastAction,
                'data' => $data
            );
            $this->saveLog(2, $clientId, '', $response, 'transaction_tc_split_payments');

            return $response;
        }

        $arrResponse['success'] = true;

        return $arrResponse;

    }
}