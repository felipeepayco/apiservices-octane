<?php
namespace App\Service\V2\Product\Validations;

use App\Helpers\Validation\ValidateError;
use App\Http\Validation\Validate as Validate;
use Illuminate\Http\Request;

class ToggleProductValidation
{
  public $response;
  public function validate(Request $request)
  {
    $validate = new Validate();
    $data = $request->all();
    if (isset($data['clientId'])) {
      $clientId = (integer) $data['clientId'];
    } else {
      $clientId = false;
    }

    if (isset($data['id'])) {
      $id = (integer) $data['id'];
    } else {
      $id = false;
    }

    if (isset($clientId)) {
      $vclientId = $validate->ValidateVacio($clientId, 'clientId');
      if (!$vclientId) {
        $validate->setError(500, "field clientId required");
      } else {
        $this->response['clientId'] = $clientId;
      }
    } else {
      $validate->setError(500, "field clientId required");
    }

    if (isset($id)) {
      $vid = $validate->ValidateVacio($id, 'id');
      if (!$vid) {
        $validate->setError(500, "field id required");
      } else {
        $this->response['id'] = $id;
      }
    } else {
      $validate->setError(500, "field id required");
    }

    if ($validate->totalerrors > 0) {
      $this->response['success'] = false;
      $this->response = ValidateError::validateError($validate);
    }

    $this->response['success'] = true;

    return $this->response;

  }

}