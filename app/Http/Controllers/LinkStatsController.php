<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use Cache;

class LinkStatsController extends Controller
{
    public function show($code_id)
    {
        $link = Cache::remember("stats.{$code_id}", 10, function () use ($code_id)
        {
            return Link::byCode($code_id);
        });

        if($link){
            $response = [
                'originalUrl' => $link->original_url,
                'newUrl' => $link->new_url,
                'shortenedUrl' => $link->shortenedUrl(),
                'useCount' => (int) $link->use_count,
                'requestCount' => (int) $link->request_count,
                'lastRequested' => $link->last_requested->toDateTimeString(),
                'lastUsed' => $link->last_used ? $link->last_used->toDateTimeString() : null,
            ];
            $arr_respuesta['success'] = true;
            $arr_respuesta['titleResponse'] = 'Stats requested';
            $arr_respuesta['textResponse'] = 'Stats requested';
            $arr_respuesta['lastAction'] = 'request Stats';
            $arr_respuesta['data'] = $response;
            return $this->crearRespuesta($arr_respuesta);
        } else {

            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = 'Link not found';
            $arr_respuesta['textResponse'] = 'Link not found';
            $arr_respuesta['lastAction'] = 'request Stats';
            $arr_respuesta['data'] = '';
            return $this->crearRespuesta($arr_respuesta);
        }



    }
}
