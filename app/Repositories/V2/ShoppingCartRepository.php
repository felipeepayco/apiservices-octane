<?php
namespace App\Repositories\V2;

use App\Models\V2\ShoppingCart;
use App\Repositories\V2\CatalogueRepository;

class ShoppingCartRepository
{

    protected $shopping_cart;
    protected $catalogueRepository;
    public function __construct(ShoppingCart $shopping_cart, CatalogueRepository $catalogueRepository)
    {
        $this->shopping_cart = $shopping_cart;
        $this->catalogueRepository = $catalogueRepository;
    }

    public function getShoppingCartWithFilters($id, $state, $initialDate, $endDate, $minAmount, $maxAmount, $filter, $limit, $clientId, $origin, $aggregation,$page)
    {
        $query = $this->shopping_cart->where('clienteId', $clientId);

        $this->constructInitQueryGetShoppingCart($query, $id, $state, $initialDate, $endDate, $minAmount, $maxAmount);
        $this->addfiltersGetShoppingCart($query, $filter);

        return $query->orderBy('fecha', 'DESC')->paginate($limit,['*'], 'page', $page);
    }
    public function getShoppingCartWithAggregations($id, $state, $initialDate, $endDate, $minAmount, $maxAmount, $filter, $limit, $clientId, $origin, $aggregation)
    {
        $query = $this->shopping_cart->where('clienteId', $clientId);

        if ($aggregation) {

            $entregas = [
                "Pendiente" => "No aplica",
                "Programado" => "envio_programado",
                "Entregado" => "entregado",
            ];

            $pagos = [
                "Rechazado" => "Rechazada",
                "Pendiente" => "No aplica",
                "Aprobado" => "Aceptada",
            ];

            $carritos = [
                "Activo" => "activo",
                "Abandonado" => "abandonado",
                "Eliminado" => "eliminado",
                "Procesando_pago" => "procesando_pago",
                "Completado" => "pagado",
            ];
            $catalogoCounts = [];

            $catalogos = $this->catalogueRepository->getCataloguesByStatus($clientId, true, ["id", "nombre"]);

            $entregaCounts = [];
            foreach ($entregas as $name => $value) {
                $query = $this->shopping_cart->where('clienteId', $clientId);
                $this->constructInitQueryGetShoppingCart($query, $id, $state, $initialDate, $endDate, $minAmount, $maxAmount);
                $entregaCounts[$name] = $query->whereRaw(['estado_entrega' => ['$regex' => $value, '$options' => 'i']])->count();
            }

            $pagoCounts = [];
            foreach ($pagos as $name => $value) {
                $query = $this->shopping_cart->where('clienteId', $clientId);
                $this->constructInitQueryGetShoppingCart($query, $id, $state, $initialDate, $endDate, $minAmount, $maxAmount);
                $pagoCounts[$name] = $query->whereRaw(['ultimo_estado_pago' => ['$regex' => $value, '$options' => 'i']])->count();
            }

            $carritoCounts = [];
            foreach ($carritos as $name => $value) {
                $query = $this->shopping_cart->where('clienteId', $clientId);
                $this->constructInitQueryGetShoppingCart($query, $id, $state, $initialDate, $endDate, $minAmount, $maxAmount);
                $carritoCounts[$name] = $query->whereRaw(['estado' => ['$regex' => $value, '$options' => 'i']])->count();
            }

            if (count($catalogos)) {
                foreach ($catalogos as $name => $id) {
                    $query = $this->shopping_cart->where('clienteId', $clientId);
                    $this->constructInitQueryGetShoppingCart($query, false, $state, $initialDate, $endDate, $minAmount, $maxAmount);
                    $catalogoCounts[$name] = $query->where('catalogo_id', $id)->count();
                }

            }
            $query = $this->shopping_cart->where('clienteId', $clientId);
            $this->constructInitQueryGetShoppingCart($query, "", $state, $initialDate, $endDate, $minAmount, $maxAmount);
            $totalCount = $query->count();

            return [
                'Entrega' => $entregaCounts,
                'Catalogos' => $catalogoCounts,
                'Carrito' => $carritoCounts,
                'Total' => ["total" => $totalCount],
                'Pago' => $pagoCounts,
            ];

        }
    }

    private function constructInitQueryGetShoppingCart(&$query, $id, $state, $initialDate, $endDate, $minAmount, $maxAmount)
    {
        if ($id) {
            $query->where('id', $id);
        }

        if ($state) {
            if ($state !== "rechazada") {
                $query->where('estado', $state);
            } else {
                $query->where('ultimo_estado_pago', $state);
            }
        }

        if ($initialDate && $endDate) {
            $initialDateTime = strtotime($initialDate);
            $endDateTime = strtotime($endDate);

            // Sumar un día a la fecha final
            $endDateTime = strtotime("+1 day", $endDateTime);

            $initialDate = date('Y-m-d H:i:s', $initialDateTime);
            $endDate = date('Y-m-d H:i:s', $endDateTime);

            $query->whereBetween('fecha', [$initialDate, $endDate]);
        }

        if ($minAmount && $maxAmount) {
            $query->whereBetween('total', [$minAmount, $maxAmount]);
        }
    }

    public function addfiltersGetShoppingCart(&$query, $filter)
    {
        if (!empty($filter)) {
            foreach ($filter as $item) {
                if ($item["valor1"] !== "") {
                    if (is_string($item["valor1"])) {
                        $query->where([$item["campo"] => ['$regex' => $item["valor1"], '$options' => 'i']]);
                    } else {
                        $query->where($item["campo"], $item["valor1"]);
                    }

                }
            }
        }
    }

    public function searchProductSeller($productIds)
    {
        $query = $this->shopping_cart->where('estado', 'pagado');
        $query->whereBetween('fecha', [
            date('Y-m-01'),
            date('c'),
        ]);
        $query->whereIn('productos.id', $productIds);

        // Ejecutar la consulta
        return $query->get();
    }

    public function getById($id, $length = null)
    {
        $query = $this->shopping_cart->where('id', $id);

        if ($length) {
            $query = $query->take($length);

        }

        return $query = $query->get();

    }

    public function getByIdAndClient($id, $client_id)
    {
        return $this->shopping_cart->where('id', $id)->where('clienteId', $client_id)->get();

    }

    public function findById($id)
    {
        return $this->shopping_cart->where('id', $id)->first();

    }

    public function findByIdAndClient($id, $client_id, $status = null)
    {
        $query = $this->shopping_cart->where('id', $id)->where('clienteId', $client_id);

        if ($status) {
            $query = $query->where('estado', $status);

        }
        return $query->first();

    }

    public function create($data)
    {
        $cart = new ShoppingCart($data);
        return $anukisResponse = $cart->save();

    }

    public function update($data, $criteria)
    {
        return $this->shopping_cart->where($criteria)->update($data);

    }


    public function disableRecords($data, $criteria,$states)
    {

        return $this->shopping_cart->where($criteria)->where('estado',$states)
        ->update($data);
    }

    public function findByCriteria($arr, $order = false)
    {

        $shopping_cart = $this->shopping_cart->where($arr);

        if ($order) {
            $shopping_cart = $shopping_cart->orderBy('fecha', 'DESC');
        }

        return $shopping_cart = $shopping_cart->first();

    }

    public function getByCriteria($arr, $order = false)
    {

        $shopping_cart = $this->shopping_cart->where($arr);

        if ($order) {
            $shopping_cart = $shopping_cart->orderBy('fecha', 'DESC');
        }

        return $shopping_cart = $shopping_cart->get();

    }

}
