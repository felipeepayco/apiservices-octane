<?php

namespace App\Repositories\V2;

use App\Helpers\Messages\CommonText;
use App\Models\V2\Catalogue;
use Carbon\Carbon;

class CatalogueRepository
{
    protected $catalogues;
    public function __construct(Catalogue $catalogues)
    {
        $this->catalogues = $catalogues;
    }

    public function listCatalogueParameterized($query, $pageSize, $page)
    {
        $queryBuilder = $this->catalogues->query();
        foreach ($query as $field => $value) {
            if (is_array($value)) {
                $queryBuilder->whereIn($field, $value);
            } else {
                $queryBuilder->where($field, $value);
            }
        }
        $result = $queryBuilder->paginate($pageSize, ['*'], 'page', $page);
        return $result->items();
    }
    public function checkDomainAndSubDomain($ownDomainValue, $ownSubDomainValue)
    {
        $query = [
            'valor_dominio_propio' => ['$eq' => $ownDomainValue],
            'valor_subdominio_propio' => ['$eq' => $ownSubDomainValue],
        ];
        return $this->catalogues->where($query)->limit(1)->get();
    }
    public function checkDomainAndSubDomainNoInMeCatalogue($ownDomainValue, $ownSubDomainValue, $catalogueId)
    {
        return $this->catalogues->where('estado', true)
            ->where('valor_dominio_propio', (string) $ownDomainValue)
            ->where('valor_subdominio_propio', (string) $ownSubDomainValue)
            ->where('id', '<>', (int) $catalogueId)->where('id', '<>', (int) $catalogueId)
            ->limit(1)->get();
    }
    public function listWithFilterWithOrdenByAsc(array $filter, $ColumnOrder)
    {
        return $this->catalogues->where($filter)->orderBy($ColumnOrder, 'asc')->get();
    }
    public function all()
    {
        return $this->catalogues->get();
    }

    public function find($id)
    {
        return $this->catalogues->where("id", (integer) $id)->first();
    }
    public function findByClient($clientID)
    {
        return $this->catalogues->where("cliente_id", $clientID)->get();
    }
    public function findByClientIdActive($clientID)
    {
        return $this->catalogues->where("cliente_id", $clientID)->where("estado", true)->get();
    }
    public function findByIdAndClientId($id, $clientId)
    {
        return $this->catalogues->where("id", $id)->where("cliente_id", $clientId)->where("estado", true)->count();
    }
    public function findByIdAndClientIdNoEstatus($id, $clientId)
    {
        return $this->catalogues->where("id", $id)->where("cliente_id", $clientId)->get();
    }
    public function findByNameAndClientId($name, $clientId)
    {
        return $this->catalogues->where("name", $name)->where("cliente_id", $clientId)->get();
    }
    public function findByNameAndClientIdAndStatus($name, $clientId, $status)
    {
        return $this->catalogues->where("name", $name)->where("cliente_id", $clientId)->where("estado", $status)->get();
    }
    public function findByParams($query)
    {
        $queryBuilder = $this->catalogues->query();
        foreach ($query as $field => $value) {
            if (is_array($value)) {
                $queryBuilder->whereIn($field, $value);
            } else {
                $queryBuilder->where($field, $value);
            }
        }
        return $queryBuilder->items();
    }
    public function create(array $data)
    {
        return $this->catalogues->create($data);
    }

    public function update($id, array $data)
    {
        return $this->catalogues->where('id', $id)->update($data);
    }
    public function updateWithClientId($id, $clientId, array $data)
    {
        return $this->catalogues->where('id', $id)->where('cliente_id', $clientId)->update($data);
    }
    public function delete($id)
    {
        $catalogue = $this->find($id);
        $catalogue->delete();

        return $catalogue;
    }

    public function findCatalogue($id, $client_id = null)
    {

        $catalogue = $this->catalogues->where('id', (integer) $id);

        if ($client_id) {
            $catalogue = $catalogue->where('cliente_id', (integer) $client_id);
        }

        return $catalogue = $catalogue->first();

    }

    public function getCatalogues($catalogue_id, $client_id = null, $origin = null, $lenght = 10)
    {

        $query = Catalogue::where('id', (int) $catalogue_id)
            ->where(CommonText::CLIENT_ID, $client_id);

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $query = $query->where('procede', $origin);
        }

        $catalogueResult = $query->take($lenght)->get();
        return $catalogueResult;

    }
    public function getCatalogueConfiguration($clientId, $pending, $isTotal = false)
    {
        $query = Catalogue::where("cliente_id", $clientId)
            ->where("estado", true)
            ->orderBy('fecha', 'desc');

        if (!$isTotal) {
            $query->where(function ($subQuery) use ($pending) {
                $subQuery->where(function ($subSubQuery) use ($pending) {
                    $subSubQuery->orWhere('progreso', 'procesando')
                        ->orWhere('progreso', 'completado');
                });

                if (!$pending) {
                    $subQuery->orWhere('progreso', 'publicado');
                }
            });
        }

        return $query->get();
    }
    public function updateInactive($clientId, $estadoPlan, $statusActivo)
    {
        $updateData = [
            'query' => ['cliente_id' => $clientId],
            'update' => [
                '$set' => ['indice' => 'catalogo'],
                '$unset' => ['activo' => ''],
            ],
        ];
        if (!empty($estadoPlan)) {
            $updateData['update']['$set']['estado_plan'] = $estadoPlan;
        }
        if (!empty($statusActivo)) {
            $updateData['update']['$set']['activo'] = $statusActivo;
        }

        return $this->catalogues->where('cliente_id', $clientId)->update($updateData);
    }

    public function getCataloguesByStatus($client_id, $status, $pluck = null)
    {
        $query = $this->catalogues->where('estado', $status)->where('cliente_id', $client_id)->get();

        if ($pluck) {
            $query = $query->pluck(...$pluck);
        }

        return $query = $query->toArray();

    }

    public function getCategories($clientId, $catalogueId, $catalogueName, $origin)
    {
        $query = $this->catalogues->where('cliente_id', $clientId)
            ->where('estado', true);

        if ($catalogueId) {
            $query->where('id', (int) $catalogueId);
        }

        if ($catalogueName) {
            $query->where('nombre', $catalogueName);
        }

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $query->where('procede', $origin);
        }

        $query->where('categorias.estado', (boolean) true)
            ->where('categorias.id', '>=', 2);

        return $catalogues = $query->get();

    }

    public function findByCriteria($arr)
    {
        return $catalogues = $this->catalogues->where($arr)->where("estado", true)->first();

    }

    public function getCataloguesWithoutCertificates()
    {

        $date = Carbon::now()->subDays(90);
        return $this->catalogues->where('dominio_propio', true)
            ->where(function ($query) {
                $query->where('intentos_certificacion', null)
                    ->orWhere('intentos_certificacion', '<=', 2);
            })->where(function ($query) {
            $dateQuery = new \MongoDB\BSON\UTCDateTime(Carbon::now()->timestamp * 1000);
            $query->where('proximo_inteto', null)
                ->orWhere('proximo_inteto', '<=', $dateQuery);
        })
            ->where(function ($query) {
                $dateQuery = new \MongoDB\BSON\UTCDateTime(Carbon::now()->subDays(90)->timestamp * 1000);
                $query->where('posee_certificado', false)
                    ->orWhere('fecha_creacion_certificado', '<', $dateQuery);
            })
            ->orderBy('posee_certificado', 'desc')
            ->orderBy('fecha_creacion_certificado', 'desc')
            ->first();
    }

    public function nextAttempt($id, $attempt = 0)
    {
        $date = Carbon::now()->addDays(1)->hour(1);
        $dateNow = new \MongoDB\BSON\UTCDateTime($date->timestamp * 1000);
        return $this->catalogues->where('id', $id)->update([
            'proximo_inteto' => $dateNow,
            'intentos_certificacion' => $attempt + 1,
        ]);

    }

    public function rebootCertificate($id)
    {
        $date = Carbon::now()->addHours(1);
        $dateNow = new \MongoDB\BSON\UTCDateTime($date->timestamp * 1000);
        return $this->catalogues->where('id', $id)->update([
            'proximo_inteto' => $dateNow,
            'intentos_certificacion' => 0,
        ]);
    }
    public function createCertificate($id)
    {
        $date = Carbon::now();
        $dateNow = new \MongoDB\BSON\UTCDateTime($date->timestamp * 1000);
        $dateAttempt = new \MongoDB\BSON\UTCDateTime(Carbon::now()->subDays(90)->hour(1)->timestamp * 1000);
        return $this->catalogues->where('id', $id)->update([
            'fecha_creacion_certificado' => $dateNow,
            'proximo_inteto' => $dateAttempt,
            'intentos_certificacion' => 0,
            'posee_certificado' => true,
        ]);
    }
}
