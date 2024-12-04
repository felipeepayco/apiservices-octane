<?php

namespace App\Models;

use Carbon\Carbon;
use App\Helpers\Math;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\NullIdException;

class Link extends Model
{
    protected $fillable = ['original_url', 'new_url', 'request_count', 'use_count', 'last_used', 'last_requested','client_id'];

    protected $dates = ['last_used', 'last_requested'];

    public function getCode()
    {

        if (!$this->id) {
            return new NullIdException;
        }

        return (new Math)->encode($this->id);
    }

    public static function byCode($new_url)
    {
        return static::whereRaw("BINARY new_url = '$new_url' ")->first();
    }

    public function shortenedUrl()
    {
        return env('SHORT_URL').'/'.$this->new_url;
    }


    public function touchTimestamp($column)
    {
        $this->{$column} = Carbon::now();
        $this->save();
    }

}
