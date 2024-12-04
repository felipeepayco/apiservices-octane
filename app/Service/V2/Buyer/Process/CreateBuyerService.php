<?php
namespace App\Service\V2\Buyer\Process;

use App\Repositories\V2\BblBuyerRepository;

class CreateBuyerService
{
    private $bblBuyerRepository;

    public function __construct(BblBuyerRepository $bblBuyerRepository)
    {
        $this->bblBuyerRepository = $bblBuyerRepository;
    }
    public function process($fieldValidation)
    {

        $data = [];
        if (isset($fieldValidation["verify"])) {

            if ($fieldValidation["verify"]) {
                $buyer = $this->bblBuyerRepository->findByCriteria(["bbl_cliente_id" => $fieldValidation["bbl_cliente_id"], "correo" => $fieldValidation["correo"]]);

                if (empty($buyer)) {
                    $data = $this->bblBuyerRepository->create($fieldValidation)->toArray();

                }else
                {
                    $data = $buyer->toArray();
                }
            }

        } else {
            $data = $this->bblBuyerRepository->create($fieldValidation);

        }

        $success = true;
        $msg = 'Cliente creado exitosamente';

        return [
            'success' => $success,
            'msg' => $msg,
            'data' => [
                "bblClientId" => $data['bbl_cliente_id'],
                "email" => $data['correo'],
                "name" => $data['nombre'],
                "lastName" => $data['apellido'],
                "document" => $data['documento'],
                "phone" => $data['telefono'],
                "phoneCode" => $data['ind_pais_tlf'],
                "country" => $data['pais'],
                "countryCode" => $data['codigo_pais'],
                "codeDane" => $data['codigo_dane'],
                "departament" => $data['departamento'],
                "city" => $data['ciudad'],
                "address" => $data['direccion'],
                "otherDetails" => $data['otros_detalles'],
                "updatedAt" => $data['updated_at'],
                "createdAt" => $data['updated_at'],
                "id" => $data['id']
            ],
        ];
    }
}
