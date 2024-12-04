<?php

namespace App\Listeners\Services;

use App\Helpers\Validation\CommonValidation;
use App\Models\TipoDocumentos;
use Exception;

class FillLatamValidationService
{

    const MOBILE_PHONE_MIN = 7; // minimo de caracteres numero telefonico
    /**
    * @return type
    * @throws Exception
    */
    public function validateDocNumberType(
        &$data,
        &$arrResponse,
        &$validate
        
        ){
          

        $docNumber = CommonValidation::validateIsSet($data,'docNumber', false);         
        $docType = CommonValidation::validateIsSet($data,'docType', false);
        $mobilePhone = CommonValidation::validateIsSet($data,'mobilePhone', false);
        $confCountry = CommonValidation::validateIsSet($data,'confCountry', false); 
        $country = CommonValidation::validateIsSet($data,'country', false); 
        $gateway = CommonValidation::validateIsSet($data,'gateway', false);

        $documents = TipoDocumentos::select('codigo', 'empresa', 'persona', 'validacion')
                    ->where('id_conf_pais', $confCountry)
                    ->get()->toArray();
        
        $documentFilter = array_filter($documents, function($item) use($docType){
            return $item["codigo"] == $docType;
        });
        
        $this->checkDocNumber($docNumber, array_values($documentFilter), $validate);


        // Mobile Phone
        
        if ($gateway) {
            $arrResponse['mobilePhone'] = $mobilePhone;
        }
        else{
            
            $vmobilePhone = $validate->ValidateVacio($mobilePhone, 'mobilePhone');
            if (!$vmobilePhone) {
                $error = $validate->getErrorCheckout("A001");
                $validate->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'mobilePhone']));
            } else {
                $vmobilePhone = $validate->ValidateCellPhone($mobilePhone, self::MOBILE_PHONE_MIN);
                if ($vmobilePhone) {
                    $arrResponse['mobilePhone'] = $mobilePhone;
                } else {
                    $error = $validate->getErrorCheckout("A003");                    
                    $validate->setError($error->error_code, "field mobilePhone must be between 7 and 15");
                }
            }
        }

        $arrResponse['docNumber']= $docNumber;
        $arrResponse['docType']= $docType;
        $arrResponse['mobilePhone']= $mobilePhone;
        $arrResponse['confCountry']= $confCountry;
        $arrResponse['country']= $country;

        return $arrResponse;
    }

    public function checkDocNumber($docNumber, $documentFilter, &$validate){

        if(strlen($docNumber) > $documentFilter[0]["validacion"]){
            $error = $validate->getErrorCheckout("E003");
                    $validate->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'docNumber']));
        }   
        
    }

    public function validateMultiAccount($data,&$arrResponse,&$validate){

        $isMultiAccount = isset($data["multiAccount"]);

        $arrResponse["isMultiAccount"] = isset($data["multiAccount"]);

        if($isMultiAccount){
            $multiAccount = $data["multiAccount"];
            $multiAccountDuplicate = CommonValidation::validateIsSet($multiAccount,"duplicate",false);
            $arrResponse["multiAccountDuplicate"] = $multiAccountDuplicate;

            $multiAccountDuplicateClientId = CommonValidation::validateIsSet($multiAccount,"duplicateClientId","");
            $arrResponse["multiAccountDuplicateClientId"] = $multiAccountDuplicateClientId;

            if($multiAccountDuplicate && !is_int($multiAccountDuplicateClientId)){
                $validate->setError(500, 'field duplicateClientId is invalid');
            }

            $multiAccountGrantUserId =  CommonValidation::validateIsSet($multiAccount,"grantUserId","");
            if(!is_int($multiAccountGrantUserId)){
                $validate->setError(500, 'field grantUserId is invalid');
            }
            $arrResponse["multiAccountGrantUserId"] = $multiAccountGrantUserId;

            $multiAccountName = CommonValidation::validateIsSet($multiAccount,"accountName","");
            CommonValidation::validateParamFormat($arrResponse,$validate,$multiAccountName,"multiAccountName","empty",true);

            $multiAccountCurrencyCode = CommonValidation::validateIsSet($multiAccount,"currencyCode","");
            CommonValidation::validateParamFormat($arrResponse,$validate,$multiAccountCurrencyCode,"multiAccountCurrencyCode","empty",true);

        }
    }
}
