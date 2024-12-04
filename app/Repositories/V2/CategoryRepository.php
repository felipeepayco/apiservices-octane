<?php
namespace App\Repositories\V2;

use App\Helpers\Messages\CommonText;
use App\Models\V2\Catalogue;
use App\Models\V2\Category;
use Carbon\Carbon;

class CategoryRepository
{

    protected $catalogues;
    protected $category;
    public function __construct(Catalogue $catalogues, Category $category)
    {
        $this->catalogues = $catalogues;
        $this->category = $category;
    }

    public function checkCategory($catalogue_id, $client_id, $categoryName, $status = true)
    {
        return $query = $this->catalogues->where('cliente_id', $client_id)
            ->where('id', $catalogue_id)
            ->where('categorias.estado', true)
            ->where('categorias.id', '>=', 2)
            ->where('categorias.nombre', $categoryName);
    }

    public function getCategories($client_id, $status = true, $catalogue_id = null, $catalogue_name = null, $origin, $category_id = null, $category_name = null)
    {
        $query = $this->catalogues->where("cliente_id", $client_id)
            ->where('estado', $status);

        if ($catalogue_id) {
            $query->where('id', $catalogue_id);
        }
        if ($catalogue_name) {
            $query->where('nombre', $catalogue_name);
        }
        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $query->where('procede', $origin);
        }

        // CATEGORIES
        if ($category_id) {
            $query->where('categorias.id', $category_id);
        }
        if ($category_name) {
            $query->where('categorias.nombre', $category_name);
        }
        $query->where('categorias.estado', true)
            ->where('categorias.id', '>=', 2);

        return $data = $query->get();
    }

    public function categoriesInCatalogue($client_id, $category_id = null, $status = true, $length = 1)
    {

        $categories = $this->catalogues->where('cliente_id', $client_id);

        if ($category_id) {
            $categories = $categories->where('categorias.id', (int) $category_id);
        }

        if ($status) {
            $categories = $categories->where('categorias.estado', $status);

        }

        return $categories = $categories->take($length)->get();

    }

    public function updateCategories($client_id, $category_id, $status)
    {
        return $updated = $this->catalogues->where('cliente_id', $client_id)
            ->where('categorias.id', $category_id)
            ->update(['categorias.$.estado' => $status]);
    }

    public function updateCategoriesByCatalogueId($catalogue_id, $category_id, $nombre, $image, $active)
    {
        return $this->catalogues->where('id', $catalogue_id)
            ->where('categorias.id', $category_id)
            ->update([
                'categorias.$.nombre' => $nombre,
                'categorias.$.img' => $image,
                'categorias.$.fecha_actualizacion' => Carbon::now(),
                'categorias.$.activo' => $active
            ]);
    }
    public function inactiveByClientId($clientId, bool $estado)
    {
        $data = [
            "estado" => $estado,
        ];
        $this->category->where("cliente_id", $clientId)->update($data);
    }




}
