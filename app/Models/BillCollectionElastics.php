<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int $id
 * @property string $information_invoice
 */
class BillCollectionElastics extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bill_collection_elastic';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'information_invoice'
    ];

}
