<?php
namespace App\Service\V2\Product\Validations;

use App\Helpers\Validation\ValidateError;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ListProductValidation
{
    public $response;
    public function validate(Request $request)
    {
        
        $validate=new Validate();
        $data=$request->all();
        if(isset($data['clientId'])){
            $clientId = (integer)$data['clientId'];
        } else {
            $clientId = false ;
        }

        if(isset($data["filter"])){
            if(is_array($data["filter"])){
                $filter=(object)$data["filter"];
            }else if(is_object($data["filter"])){
                $filter=$data["filter"];
            }else{
                $validate->setError(422,"field filter is type object");
            }
        }else{
            $filter=[];
        }

        $arr_respuesta["filter"]=$filter;

        if(isset($clientId)){
            $vclientId = $validate->ValidateVacio($clientId, 'clientId');
            if (!$vclientId) {
                $validate->setError(422, "field clientId required");
            } else{
                $arr_respuesta['clientId'] = $clientId;
            }
        }else{
            $validate->setError(422, "field clientId required");
        }
        $pagination = [];
        if (isset($data["pagination"])) {
            if (is_array($data["pagination"])) {
                $pagination = (object)$data["pagination"];

                if (isset($data["pagination"]["page"])) {
                    if (!$validate->validateIsNumeric($data["pagination"]["page"])) {
                        $validate->setError(422, "page field must be an integer");

                    } else {
                        if ($data["pagination"]["page"] < 1) {
                            $validate->setError(422, "page field must be greater than or equal to 1");

                        }
                    }

                }
                if (isset($data["pagination"]["limit"])) {
                    if (!$validate->validateIsNumeric($data["pagination"]["limit"])) {
                        $validate->setError(422, "limit field must be an integer");

                    } else {
                        if ($data["pagination"]["limit"] < 1) {
                            $validate->setError(422, "limit field must be greater than or equal to 1");

                        }
                    }
                }
            } else if (is_object($data["pagination"])) {
                $pagination = $data["pagination"];
            } else {
                $validate->setError(422, "field pagination is type object");
            }
        }

        $arr_respuesta["pagination"] = $pagination;

        
        if( $validate->totalerrors > 0 ){
            $arr_respuesta['success'] = false;
            $this->response = ValidateError::validateError($validate);
            
        } else {
            $arr_respuesta['success'] = true;
            $this->response =$arr_respuesta;
        }
        return $arr_respuesta['success'];


    }
}
