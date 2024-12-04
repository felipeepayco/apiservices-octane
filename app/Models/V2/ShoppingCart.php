<?php
namespace App\Models\V2;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
class ShoppingCart extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'shoppingcarts';
    protected $primaryKey = 'id';
    protected $guarded = [];


}
