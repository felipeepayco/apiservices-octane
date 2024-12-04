<?php

namespace App\Listeners\ShoppingCart\Validation;


use App\Events\ShoppingCart\Validation\ValidationSetShippingInfoEvent;
use App\Helpers\Pago\HelperPago;
use App\Http\Validation\SMTPValidateEmail\Exceptions\Exception;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;
use App\Models\Catalogo;
use App\Helpers\Validation\CommonValidation;
use App\Helpers\Messages\CommonText as CT;

class ValidationSetShippingInfoListener extends HelperPago
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

    public function handle(ValidationSetShippingInfoEvent $event)
    {

        $validate = new Validate();
        $data = $event->arr_parametros;
        $discountAmount = CommonValidation::validateIsSet($data, 'discountAmount', 0, 'float');
        $arr_respuesta['discountAmount'] = $discountAmount;

        if (isset($data['clientId'])) {
            $clientId = (int) $data['clientId'];
        } else {
            $clientId = false;
        }

        if (isset($data['conditions'])) {
            $conditions = (int) $data['conditions'];
        } else {
            $conditions = false;
        }

        if (isset($data['saveInfoShipping'])) {
            $arr_respuesta['saveInfoShipping'] = (bool) $data['saveInfoShipping'];
        } else {
            $arr_respuesta['saveInfoShipping'] = false;
        }
        
        if (isset($data['country'])) {
            $arr_respuesta['country'] = $data['country'];
        } else {
            $arr_respuesta['country'] = "";
        }

        if (isset($data['region'])) {
            $arr_respuesta['region'] = $data['region'];
        } else {
            $arr_respuesta['region'] = "";
        }
        
        if (isset($data['terms'])) {
            $terms = (int) $data['terms'];
        } else {
            $terms = false;
        }
        
        if (isset($data['id'])) {
            $id = $data['id'];
        } else {
            $id = false;
        }

        if (isset($data['ip'])) {
            $ip = $data['ip'];
        } else {
            $ip = false;
        }

        if (isset($data['name'])) {
            $name = $data['name'];
        } else {
            $name = false;
        }
        if (isset($data['lastName'])) {
            $arr_respuesta['lastName'] = $data['lastName'];
        } else {
            $arr_respuesta['lastName'] = "";
        }
        if (isset($data['discountCodes']) && is_array($data['discountCodes'])) {
            $arr_respuesta['discountCodes'] = $data['discountCodes'];
        } else {
            $arr_respuesta['discountCodes'] = [];
        }


        if (isset($data['phone'])) {
            $phone = (int)$data['phone'];
        } else {
            $phone = false;
        }

        if (isset($data['address'])) {
            $address = $data['address'];
        } else {
            $address = false;
        }

        if (isset($data['property'])) {
            $property = $data['property'];
        } else {
            $property = false;
        }

        if (isset($data['city'])) {
            $city = $data['city'];
        } else {
            $city = false;
        }

        if (isset($data['other'])) {
            $arr_respuesta['other'] = $data['other'];
        } else {
            $arr_respuesta['other'] = "";
        }

        if (isset($data['shippingAmount'])) {
            $shippingAmount = $data['shippingAmount'];
        } else {
            $shippingAmount = false;
        }
        if (isset($data['amount'])) {
            $amount = $data['amount'];
        } else {
            $amount = false;
        }

        if (isset($data[CT::LANDING_IDENTIFIER])) {
            $landingIdentifier = $data[CT::LANDING_IDENTIFIER];
        } else {
            $landingIdentifier = false;
        }

        if (isset($data[CT::CONTACT_NAME])) {
            $contactName = $data[CT::CONTACT_NAME];
        } else {
            $contactName = false;
        }

        if (isset($data[CT::CONTACT_PHONE])) {
            $contactPhone = $data[CT::CONTACT_PHONE];
        } else {
            $contactPhone = false;
        }

        if (isset($data[CT::DOCUMENT_TYPE])) {
            $documentType = $data[CT::DOCUMENT_TYPE];
        } else {
            $documentType = false;
        }

        if (isset($data[CT::DOCUMENT_NUMBER])) {
            $documentNumber = $data[CT::DOCUMENT_NUMBER];
        } else {
            $documentNumber = false;
        }

        if (isset($data[CT::EMAIL])) {
            $email = $data[CT::EMAIL];
        } else {
            $email = false;
        }

        if (isset($data[CT::FRANCHISE])) {
            $franchise = $data[CT::FRANCHISE];
        } else {
            $franchise = false;
        }

        if (isset($data[CT::QUOTE_EN])) {
            $quote = $data[CT::QUOTE_EN];
        } else {
            $quote = null;
        }

        if (isset($data[CT::CODEDANE_EN])) {
            $codeDane = $data[CT::CODEDANE_EN];
        } else {
            $codeDane = "";
        }

        if (isset($clientId)) {
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(500, "field clientId required");
            } else {
                $arr_respuesta['clientId'] = $clientId;
            }
        } else {
            $validate->setError(500, "field clientId required");
        }

        if (isset($landingIdentifier) && $landingIdentifier !== 'EPAYCO') {
            if (isset($franchise)) {
                $vfranchise = $validate->ValidateVacio($franchise, CT::FRANCHISE);
                if (!$vfranchise) {
                    $validate->setError(500, "field franchise required");
                } else {
                    $arr_respuesta[CT::FRANCHISE] = $franchise;
                }
            } else {
                $validate->setError(500, "field franchise required");
            }
        }

        if (isset($id)) {
            $vId = $validate->ValidateVacio($id, 'id');
            if (!$vId) {
                $validate->setError(500, "field id required");
            } else {
                $arr_respuesta['id'] = $id;
            }
        } else {
            $validate->setError(500, "field id required");
        }

        if (isset($ip)) {
            $vIp = $validate->ValidateVacio($ip, 'ip');
            if (!$vIp) {
                $validate->setError(500, "field ip required");
            } else {
                $arr_respuesta['ip'] = $ip;
            }
        } else {
            $validate->setError(500, "field ip required");
        }

        if (isset($name)) {
            // El nombre no puede estar vacio ni contener mas de 40 caracteres
            $vname = $validate->ValidateStringSize($name, 1,40);
            if (!$vname) {
                $validate->setError(500, "field name is empty or too long");
            } else {
                $arr_respuesta['name'] = $name;
            }
        } else {
            $validate->setError(500, "field name is empty or too long");
        }

        if (isset($phone)) {
            if (isset($landingIdentifier) && $landingIdentifier !== 'EPAYCO') {
                $vphone = $validate->ValidateCellPhone($phone);
                if (!$vphone) {
                    $validate->setError(500, "field phone required");
                } else {
                    $arr_respuesta['phone'] = $phone;
                }
            } else {
                $vphone = $validate->ValidateCellPhoneSize($phone, 1, 15);
                if (!$vphone) {
                    $validate->setError(500, "the field phone 1 between 15 character");
                } else {
                    $arr_respuesta['phone'] = $phone;
                }
            }
        }

        if (isset($conditions)) {
            $vconditions = $validate->ValidateVacio($conditions,"conditions");
            if (!$vconditions) {
                $validate->setError(500, "field conditions required");
            } else {
                $arr_respuesta['conditions'] = $conditions;
            }
        } else {
            $validate->setError(500, "field conditions required");
        }

        if (isset($terms)) {
            $vterms = $validate->ValidateVacio($terms,"terms");
            if (!$vterms) {
                $validate->setError(500, "field terms required");
            } else {
                $arr_respuesta['terms'] = $terms;
            }
        } else {
            $validate->setError(500, "field terms required");
        }
        
        if (isset($address)) {
            $vaddress = $validate->ValidateVacio($address, 'address');
            if (!$vaddress) {
                $validate->setError(500, "field address required");
            } else {
                $arr_respuesta['address'] = $address;
            }
        } else {
            $validate->setError(500, "field address required");
        }
        
        if (isset($property)) {
            // El nombre no puede estar vacio ni contener mas de 35 caracteres
            $vproperty = $validate->ValidateStringSize($property, 1,35);
            if (!$vproperty) {
                $validate->setError(500, "field property is empty or too long");
            } else {
                $arr_respuesta['property'] = $property;
            }
        } else {
            $validate->setError(500, "field property is empty or too long");
        }

        if (isset($shippingAmount)) {
            $vshippingAmount = $validate->ValidateVacio($shippingAmount, 'shippingAmount');
            if (!$vshippingAmount) {
                $validate->setError(500, "field shippingAmount required");
            } else {
                $arr_respuesta['shippingAmount'] = $shippingAmount;
            }
        } else {
            $validate->setError(500, "field shippingAmount required");
        }
        if (isset($amount)) {
            $vamount = $validate->ValidateVacio($amount, 'amount');
            if (!$vamount) {
                $validate->setError(500, "field amount required");
            } else {
                $arr_respuesta['amount'] = $amount;
            }
        } else {
            $validate->setError(500, "field amount required");
        }

        if (isset($landingIdentifier)) {
            $vlandingIdentifier = $validate->ValidateVacio($landingIdentifier, CT::LANDING_IDENTIFIER);
            if (!$vlandingIdentifier) {
                $validate->setError(500, "field landingIdentifier required");
            } else {
                $arr_respuesta[CT::LANDING_IDENTIFIER] = $landingIdentifier;
            }
        } else {
            $validate->setError(500, "field landingIdentifier required");
        }

        if (isset($contactPhone)) {
            if (isset($landingIdentifier) && $landingIdentifier !== 'EPAYCO') {
                $vcontactPhone = $validate->ValidateCellPhone($contactPhone);
                if (!$vcontactPhone) {
                    $validate->setError(500, "field contactPhone required");
                } else {
                    $arr_respuesta[CT::CONTACT_PHONE] = $contactPhone;
                }
            } else {
                $vcontactPhone = $validate->ValidateCellPhoneSize($contactPhone, 1, 15);
                if (!$vcontactPhone) {
                    $validate->setError(500, "the field contactPhone 1 between 15 character");
                } else {
                    $arr_respuesta[CT::CONTACT_PHONE] = $contactPhone;
                }
            }
        } else {
            $validate->setError(500, "field contactPhone required");
        }

        if (isset($contactName)) {
            $vcontactName = $validate->ValidateVacio($contactName, CT::CONTACT_NAME);
            if (!$vcontactName) {
                $validate->setError(500, "field contactName required");
            } else {
                $arr_respuesta[CT::CONTACT_NAME] = $contactName;
            }
        } else {
            $validate->setError(500, "field contactName required");
        }

        if (isset($landingIdentifier) && $landingIdentifier !== 'EPAYCO') {
            if (isset($documentType)) {
                $vdocumentType = $validate->ValidateVacio($documentType, CT::DOCUMENT_TYPE);
                if (!$vdocumentType) {
                    $validate->setError(500, "field documentType required");
                } else {
                    $arr_respuesta[CT::DOCUMENT_TYPE] = $documentType;
                }
            } else {
                $validate->setError(500, "field documentType required");
            }

        }
        if (isset($documentNumber)) {
            $vdocumentNumber = $validate->ValidateVacio($documentNumber, CT::DOCUMENT_NUMBER);
            if (!$vdocumentNumber) {
                $validate->setError(500, "field documentNumber required");
            } else {
                $arr_respuesta[CT::DOCUMENT_NUMBER] = $documentNumber;
            }
        } else {
            $validate->setError(500, "field documentNumber required");
        }

        if (isset($city)) {
            $vcity = $validate->ValidateVacio($city, 'city');
            if (!$vcity) {
                $validate->setError(500, "field city required");
            } else {
                $arr_respuesta['city'] = $city;
            }
        } else {
            $validate->setError(500, "field city required");
        }

        if (isset($email)) {
            $vemail = $validate->ValidateEmail($email, CT::EMAIL);
            if (!$vemail) {
                $validate->setError(500, "field email required");
            } else {
                $arr_respuesta[CT::EMAIL] = $email;
            }
        } else {
            $validate->setError(500, "field email required");
        }

        $arr_respuesta[CT::QUOTE_EN] = $quote;
        $arr_respuesta[CT::CODEDANE_EN] = $codeDane;

        if ($validate->totalerrors > 0) {

            $success         = false;
            $last_action    = 'validation id ';
            $title_response = 'Error';
            $text_response  = 'Some fields are required, please correct the errors and try again';

            $data           =
                array(
                    'totalerrors' => $validate->totalerrors,
                    'errors' => $validate->errorMessage
                );
            return  array(
                'success'         => $success,
                'titleResponse' => $title_response,
                'textResponse'  => $text_response,
                'lastAction'    => $last_action,
                'data'           => $data
            );
        }

        $arr_respuesta['success'] = true;

        return $arr_respuesta;
    }
}
