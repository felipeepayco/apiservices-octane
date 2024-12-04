<?php

namespace App\Observers;


use App\Models\Link;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LinkObserver
{
    public function created(Link $link)
    {
        $link->update([
            'new_url' => $link->getCode(),
            'last_requested' => Carbon::now()
        ]);
    }
}
