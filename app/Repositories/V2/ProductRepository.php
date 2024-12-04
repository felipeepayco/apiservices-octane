<?php

namespace App\Repositories\V2;

use App\Helpers\Messages\CommonText;
use App\Helpers\RemplaceValue;
use App\Models\V2\Product;

class ProductRepository
{
    protected $products;
    public function __construct(Product $products)
    {
        $this->products = $products;
    }

    public function all()
    {
        return $this->products->get();
    }
    public function listProductsByCatalogue($catalogueId)
    {
        $query = $this->products->where('catalogo_id', $catalogueId);
        $query->where('estado', 1);
        $query->where('activo', true);
        return $query->get();
    }
    public function getTotalSoldByCatalogue($catalogueId)
    {
        $query = $this->products->where('catalogo_id', $catalogueId)->where('activo',true)->where('estado',1);
        return $query->sum('ventas');
    }
    public function getTotalAvaliableByCatalogue($catalogueId)
    {
        $query = $this->products->where('catalogo_id', $catalogueId)->where('activo',true)->where('estado',1);
        return $query->sum('disponible');
    }
    public function find($id)
    {
        return $this->products->where("id", (integer) $id)->first();
    }
    public function getSum($column)
    {
        return $this->products->sum($column);
    }
    public function findByClient($clientID)
    {
        return $this->products->where("cliente_id", $clientID);
    }
    public function create(array $data)
    {
        return $this->products->create($data);
    }

    public function update($id, array $data)
    {

        return $this->products->where('id', $id)->update($data);
    }

    public function updateByCriteria($criteria, array $data)
    {

        return $this->products->where($criteria)->update($data);
    }


    public function delete($id)
    {
        $products = $this->find($id);
        $products->delete();

        return $products;
    }
    public function updateByCatalogueId($catalogueId, array $data)
    {
        return $this->products->where('catalogo_id', $catalogueId)->update($data);
    }

    public function updateCategoriesInProduct($category_id, $status = false, $categories = null)
    {

        $update_arr = [];
        $update_arr["activo"] = $status;

        if ($categories) {
            $update_arr["categorias"] = $categories;

        }
        $this->products->where('categorias', (int) $category_id)->update($update_arr);
    }

    public function updateCategoriesInProductById($enabledProductsId, $active = false)
    {
        $this->products->whereIn('id', $enabledProductsId)->update([CommonText::ACTIVE => $active]);

    }

    public function countCategoriesInProduct($category_id, $active = false)
    {
        return $this->products->where(CommonText::CATEGORIES, $category_id)->where(CommonText::ACTIVE, $active)->count();

    }

    public function getCategoriesInProduct($category_id, $origin)
    {
        $productsQuery = $this->products->where('estado', (int) 1)
            ->whereIn('categorias', [(int) $category_id,(string) $category_id]);

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $productsQuery
                ->where('activo', (boolean) true)
                ->where('disponible', '>=', (int) 1);
        }

        return $productsInCategoryResult = $productsQuery->take(10)
            ->skip(0)
            ->get();
    }

    public function listProductoFilter($filters)
    {
        $clientId = $filters['clientId'];

        $query = $this->products->where('cliente_id', $clientId)
            ->where('estado', 1)
            ->where('origen', 'epayco');

        // //busqueda 
        if (isset($filters['filter'])) {
            $keysToExclude = ['order', 'manage', 'name', 'value'];
            $orConditions = [];
            
            foreach ($filters['filter'] as $key => $value) {
                if (!in_array($key, $keysToExclude)) {
                    $param = RemplaceValue::getReplacementValues($key, $value);
                    
                    if ($param['key'] === "titulo") {
                        if(isset($filters['mode']) && $filters['mode']==2){
                            $words = $this->generateWordsForQuery($param['value']);
                            $orConditions[] = function ($q) use ($words, $param) {
                                foreach ($words as $word) {
                                    $q->orWhere($param['key'], 'like', '%' . $word . '%');
                                    $q->orWhere('referencias.nombre', 'like', '%' . $word . '%');
                                }
                            };
                        }else{
                            $words=$this->generateWordPairs($param['value']);
                            if(count($words)>0){
                                $orConditions[] = function ($q) use ($param, $words) {
                               
                                        foreach ($words as $word) {
                                            $q->orWhere(function ($subSubQ) use ($param, $word) {
                                                $subSubQ->where($param['key'], 'like', '%' . $word[0] . '%')
                                                    ->where('referencias.nombre', 'like', '%' . $word[1] . '%');
                                            });
                                        }
                                  
                                };
                            }else{
                                $orConditions[] = function ($q) use ($words, $param) {
                                 
                                        $q->orWhere($param['key'], 'like', '%' . $param['value'] . '%');
                                        $q->orWhere('referencias.nombre', 'like', '%' . $param['value'] . '%');
                                    
                                };             
                            }

                        }

                    } else {
                        $orConditions[] = [$param['key'] => [(int) $param['value'], (string) $param['value'], $param['value']]];
                    }
                }
            }
        
            if (!empty($orConditions)) {
                $query->where(function ($q) use ($orConditions) {
                    foreach ($orConditions as $condition) {
                        if (is_callable($condition)) {
                            $q->where($condition);
                        } else {
                            $q->whereIn(key($condition), $condition[key($condition)]);
                        }
                    }
                });
            }
        }

        // Paginación
        $pagination = (object) (isset($filters["pagination"]) ? $filters["pagination"] : []);
        $page = isset($pagination) && isset($pagination->page) ? $pagination->page : 1;
        $pageSize = isset($pagination) && isset($pagination->limit) ? $pagination->limit : 50;
        $query->skip(($page - 1) * $pageSize);
        $query->limit($pageSize);

        // Ordenamiento
        $order = isset($filters['filter']->order) ? $filters['filter']->order : '';
        if ($order === 'date_asc') {
            $query->orderBy('fecha', 1);
        } elseif ($order === 'outstanding') {
            $query->orderBy('destacado', -1);
        } elseif ($order === 'topSelling') {
            $query->orderBy('ventas', -1);
        } else {
            $query->orderBy('fecha', -1);
        }

        // Ejecutar la consulta
        $totalCount = $query->clone()->skip(0)->limit(0)->count();
        $resultProducts = $query->get();
        return array($resultProducts, $clientId, $page, $pageSize, $totalCount);
    }
    private function avanceSearch(&$query,$param){
        $query->whereIn($param['key'], 'regex', '/'.$param['value'].'/i');
    }
    function deleteSpaces($value) {
        return trim($value) !== ''; // Elimina los espacios y verifica si el elemento no está vacío
    }
    function generateWordPairs($text) {
        $words = explode(' ', $text); // Obtener todas las palabras del texto
        $wordPairs = [];
        $pos=0;
        // Generar combinaciones de palabras en pares
        for ($i = 0; $i < count($words); $i++) {
            for ($j = $i + 1; $j < count($words); $j++) {
                
                $wordPairs[$pos][0] = $words[$i];
                $wordPairs[$pos][1] = $words[$j];
                $pos++;
                $wordPairs[$pos][0] = $words[$j];
                $wordPairs[$pos][1] = $words[$i];
                $pos++;
            }
        }
        return $wordPairs;
    }
    function generateWordsForQuery($text) {
        $words = explode(" ", $text);
        $newWords = [];
    
        foreach ($words as $word) {
            if (strlen($word) > 3) {
                for ($i = 3; $i <= strlen($word); $i++) {
                    $newWords[] = substr($word, 0, $i);
                }
            }
        }
    
        $combinations = [];
        
        foreach ($words as $word) {
            if (strlen($word) > 3) {
                $combinations[] = $word;
                $n = strlen($word);
                for ($i = 1; $i < $n - 2; $i++) {
                    for ($j = $i + 3; $j <= $n; $j++) {
                        $combinations[] = substr($word, $i, $j - $i);
                    }
                }
            }
        }
    
        $resultArray = array_unique(array_merge($newWords, $words, $combinations));
        $resultArray = array_filter($resultArray, function($value) {
            return trim($value) !== ''; // Remove empty or whitespace elements
        });
    
        return array_values($resultArray); // Re-index the array
    }
    public function searchProductRelated($productId, $categoryId)
    {
        $query = $this->products->where('estado', 1)
            ->where('origen', 'epayco')
            ->where('categorias',(int) $categoryId)
            ->where('id', '<>', $productId);

        // Ordenar los resultados aleatoriamente
        // Limitar los resultados a 5
        return $query->orderBy('_rand', 1)->limit(5)->get();
    }

    public function findProductWhereIn($ids, $limit = null)
    {
        $query = $this->products->whereIn('id', $ids);

        if ($limit) {
            $query = $query->limit($limit);
        }

        return $query = $query->get();

    }

    public function findProductByStatusAndCategory($id, $category, $status)
    {
        $count = $this->products->where('estado', $status)
            ->where('categorias', (int) $category)
            ->where('id', '!=', $id)
            ->count();

        $random = rand(0, max(0, $count - 5));

        return $relatedProducts = $this->products->where('estado', $status)
            ->where('categorias',(int) $category)
            ->where('id', '!=', $id)
            ->skip($random)
            ->take(5)
            ->get();

    }

    public function findByClientAndStatus($id, $clientId, $status)
    {
        return $this->products->where('cliente_id', $clientId)
            ->where('id', (int) $id)
            ->where('estado', (int) $status)
            ->first();
    }
    public function getProductsForConfigurations($clientId, $getTotalProduct, $pendingCatalogueId)
    {
        $query = $this->products->where("cliente_id", $clientId);
        if (!$getTotalProduct) {
            $query->where('catalogo_id', $pendingCatalogueId);
        }
        $results = $query->get();
        return $results;
    }
    public function inactiveByClientId($clientId, bool $estado = false)
    {
        $data = [
            "activo" => $estado,
        ];
        $this->products->where("cliente_id", $clientId)->update($data);
    }

    public function findByCriteria($arr)
    {
        return $data = $this->products->where($arr)->find();

    }


    public function getByCriteria($arr)
    {
        return $data = $this->products->where($arr)->get();

    }

    public function getByCategories($category_id, $status = 1, $active = true)
    {
        return $data = $this->products->where('estado', $status)->where('activo', $active)
            ->whereIn('categorias', [(int) $category_id, (string)$category_id])
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get();

    }

    public function getOutstandingProducts($catalogueId, $status, $outstanding, $origin)
    {
        $productsQuery = Product::where('estado', (int) $status)
            ->where('destacado', (boolean) $outstanding)
            ->where('catalogo_id',(int) $catalogueId)
            ->orderBy('fecha', 'desc');

        if ($origin == CommonText::ORIGIN_EPAYCO) {
            $productsQuery->where('activo', (boolean) true);
            $productsQuery->where('disponible', '>=', 1);
        }

        return $productsOutstanding = $productsQuery->take(10)->get();

    }

    public function getTotalActiveProducts($catalogs, $origin, $idProduct = null, $clientId = null)
    {
        $query = $this->products->where('origen', $origin);

        if (!$idProduct) {
            if (!is_null($clientId) || empty($catalogs)) {
                $query->where('cliente_id', $clientId);
            } else {
                $catalogIds = [];
                foreach ($catalogs as $catalog) {
                    $catalogIds[] = is_object($catalog) ? $catalog->id : $catalog["id"];
                }
                $query->whereIn('catalogo_id', $catalogIds);
            }

            $query->where('estado', 1)
                ->where('activo', true)
                ->orderBy('fecha', 'asc')
                ->limit(10000);

            return $query->get()->toArray();
        } else if ($idProduct != null) {
            $product = $query->where('id', $idProduct)->first();
            return $product ? [$product->toArray()] : [];
        }

        return [];
    }

    public function incrementStock($id,$quantity)
    {
        return $data = $this->products->where('id', $id)->increment('disponible', $quantity);

    }

    public function searchProductTitle($title, $clientId, $idProduct)
    {
        if($idProduct){

            return $this->products->where('cliente_id', $clientId)
                ->where('titulo', $title)
                ->where('estado', 1)
                ->where('id', '<>', $idProduct)
                ->get();
        }
        return $this->products->where('cliente_id', $clientId)
            ->where('titulo', $title)
            ->where('estado', 1)
            ->get();
    }
}








