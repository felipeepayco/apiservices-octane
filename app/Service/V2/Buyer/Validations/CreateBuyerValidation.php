<?php

namespace App\Service\V2\Buyer\Validations;

use App\Repositories\V2\BblBuyerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreateBuyerValidation
{
    private $bblBuyerRepository;
    public array $response;

    public function __construct(BblBuyerRepository $bblBuyerRepository)
    {
        $this->bblBuyerRepository = $bblBuyerRepository;
    }

    public function validation(Request $request)
    {
        $request->merge([
            'bbl_cliente_id' => $request->input('clientId'),
            'correo' => $request->input('email'),
            'nombre' => $request->input('firstName'),
            'apellido' => $request->input('lastName'),
            'documento' => $request->input('document'),
            'telefono' => $request->input('clientPhone'),
            'ind_pais_tlf' => $request->input('countryCode'),
            'codigo_pais' => $request->input('countryCode2') ?? "CO",
            'codigo_dane' => $request->input('codeDane') ?? "11001",
            'pais' => $request->input('country'),
            'departamento' => $request->input('department'),
            'ciudad' => $request->input('city'),
            'direccion' => $request->input('address'),
            'otros_detalles' => $request->input('other'),
        ]);

        $validator = Validator::make($request->all(), [
            'correo' => 'required|email|max:50',
            'nombre' => 'required|max:50|regex:/^[\pL\s\-]+$/u',
            'apellido' => 'required|max:50|regex:/^[\pL\s\-]+$/u',
            'documento' => 'required|max:13',
            'telefono' => 'required|digits_between:1,10|numeric|gt:0',
            'ind_pais_tlf' => 'required|digits_between:1,4|numeric|gt:0',
            'pais' => 'required|max:100|regex:/^[\pL\s\.\-0-9]+$/u',
            'departamento' => 'required|max:100|regex:/^[\pL\s\.\-0-9]+$/u',
            'ciudad' => 'required|max:100|regex:/^[\pL\s\.\-0-9]+$/u',
            'direccion' => 'required|max:255',
            'codigo_pais' => 'required|max:10',
            'codigo_dane' => 'required|max:10',
            'otros_detalles' => 'nullable|max:255',
            'monto_total_consumido' => 'numeric|nullable',
            'ultima_compra' => 'numeric|nullable',
            'bbl_cliente_id' => 'required|numeric',
        ], [
            'correo.required' => 'El campo correo es obligatorio.',
            'correo.email' => 'El campo correo debe ser una dirección de correo electrónico válida.',
            'correo.unique' => 'El correo ya se encuentra registrado.',

            'correo.max' => 'El campo correo no debe exceder los :max caracteres.',

            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.max' => 'El campo nombre no debe exceder los :max caracteres.',
            'nombre.regex' => 'El campo nombre solo admite valores alfabeticos.',

            'ciudad.required' => 'El campo ciudad es obligatorio.',
            'ciudad.max' => 'El campo ciudad no debe exceder los :max caracteres.',
            'ciudad.regex' => 'El campo ciudad solo admite valores alfabeticos.',

            'pais.required' => 'El campo pais es obligatorio.',
            'pais.max' => 'El campo pais no debe exceder los :max caracteres.',
            'pais.regex' => 'El campo pais solo admite valores alfabeticos.',

            'departamento.required' => 'El campo departamento es obligatorio.',
            'departamento.max' => 'El campo departamento no debe exceder los :max caracteres.',
            'departamento.regex' => 'El campo departamento solo admite valores alfabeticos.',

            'ind_pais_tlf.required' => 'El campo ind_pais_tlf es obligatorio.',
            'ind_pais_tlf.max' => 'El campo ind_pais_tlf no debe exceder los :max caracteres.',
            'ind_pais_tlf.numeric' => 'El campo ind_pais_tlf debe ser numerico.',
            'ind_pais_tlf.digits_between' => 'El campo ind_pais_tlf debe tener entre 1 y 4 digitos.',
            'ind_pais_tlf.gt' => 'El campo ind_pais_tlf debe ser mayor a 0.',

            'apellido.required' => 'El campo apellido es obligatorio.',
            'apellido.max' => 'El campo apellido no debe exceder los :max caracteres.',
            'apellido.regex' => 'El campo apellido solo admite valores alfabeticos.',

            'documento.required' => 'El campo documento es obligatorio.',
            'documento.max' => 'El campo documento no debe exceder los :max caracteres.',

            'telefono.required' => 'El campo telefono es obligatorio.',
            'telefono.numeric' => 'El campo telefono debe ser numerico.',
            'telefono.digits_between' => 'El campo telefono debe tener entre 1 y 10 digitos.',
            'telefono.gt' => 'El campo telefono debe ser mayor a 0.',

            'direccion.required' => 'El campo direccion es obligatorio.',
            'direccion.max' => 'El campo direccion no debe exceder los :max caracteres.',

            'codigo_pais.required' => 'El campo direccion es obligatorio.',
            'codigo_pais.max' => 'El campo direccion no debe exceder los :max caracteres.',

            'codigo_dane.required' => 'El campo direccion es obligatorio.',
            'codigo_dane.max' => 'El campo direccion no debe exceder los :max caracteres.',

            'otros_detalles.max' => 'El campo otros_detalles no debe exceder los :max caracteres.',

        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'titleResponse' => 'error',
                'textResponse' => $validator->errors()->all(),
                'lastAction' => 'validation',
                'data' => null,
            ];
            $this->response = $response;
            return false;
        }
        $this->response = $request->all();

        //verify exist buyer
        $this->bblBuyerRepository->documento = $request->documento;
        $this->bblBuyerRepository->bblClienteId = $request->bbl_cliente_id;
        $existingBuyer = $this->bblBuyerRepository->findBuyerByDocumentAndClientId();

        if ($existingBuyer) {
            $response = [
                'success' => false,
                'titleResponse' => 'Verify register',
                'textResponse' => "El cliente ya existe",
                'lastAction' => 'Validation',
                'data' => null,
            ];
            $this->response = $response;
            return false;
        }

        return true;
    }
}
