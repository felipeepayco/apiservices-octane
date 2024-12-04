<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use App\Models\Link;
use Cache;
use App\Http\Validation\Validate;
use App\Helpers\Edata\HelperEdata;

class LinksController extends Controller
{
    public function store(Request $request)
    {
        $validator = new Validate();
        $url = $request->get('url');

        if (isset($url)) {
            $vUrlVacio = $validator->ValidateVacio($url, '');
            $vUrlStructure = $validator->ValidateUrl($url, '');
        } else {
            $error = $validator->getErrorCheckout('AL001');
            $validator->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'URL', "fieldType" => "URL"]));

        }

        if (isset($vUrlVacio) && !$vUrlVacio) {
            $error = $validator->getErrorCheckout('AL002');
            $validator->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'URL', "fieldType" => "URL"]));

        }

        if (isset($vUrlStructure) && !$vUrlStructure) {
            $error = $validator->getErrorCheckout('AL003');
            $validator->setError($error->error_code, trans("error.{$error->error_message}", ['field' => 'URL', "fieldType" => "URL"]));
        }

        // Validaciones edata
        $edata = new HelperEdata($request, $request->get('clientId'));
        if (!$edata->validarLinkAcortado($url)) {
            $validator->setError('AED100', $edata->getMensaje());
        }

        if ($validator->totalerrors>0) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = 'Error';
            $arr_respuesta['textResponse'] = 'Error';
            $arr_respuesta['lastAction'] = 'create url';
            $arr_respuesta['data'] = $validator->errorMessage;
        } else {
            $link = Link::firstOrNew([
                'original_url' => $request->url,
                'client_id'=>$request->get('clientId')
            ]);


            if (!$link->exists) {
                $link->client_id = $request->get('clientId');
                $link->save();
                $link->new_url = $link->getCode();
            }

            $link->increment('request_count');
            $link->touchTimestamp('last_requested');
            $link->save();
            $response = [
                'originalUrl' => $link->original_url,
                'newUrl' => env('SHORT_URL').'/'.$link->new_url,
            ];
            $arr_respuesta['success'] = true;
            $arr_respuesta['titleResponse'] = 'Url created';
            $arr_respuesta['textResponse'] = 'Url created';
            $arr_respuesta['lastAction'] = 'create url';
            $arr_respuesta['data'] = $response;
        }

        return $this->crearRespuesta($arr_respuesta);
    }


    public function show($code_id, Request $request)
    {
        $link = Cache::rememberForever("link.{$code_id}", function () use ($code_id,$request) {
            return Link::byCode($code_id);
        });

        if (!$link) {
            $arr_respuesta['success'] = false;
            $arr_respuesta['titleResponse'] = 'Url not found';
            $arr_respuesta['textResponse'] = 'Url not found';
            $arr_respuesta['lastAction'] = 'search url';
            $arr_respuesta['data'] = '';
            return $this->crearRespuesta($arr_respuesta);
        }

        $link->increment('use_count');

        $link->touchTimestamp('last_used');
        $response = [
            'original_url' => $link->original_url,
            'shortened_url' => $link->shortenedUrl(),
        ];
        $arr_respuesta['success'] = true;
        $arr_respuesta['titleResponse'] = 'Url Consumed';
        $arr_respuesta['textResponse'] = 'Url Consumed';
        $arr_respuesta['lastAction'] = 'consume url';
        $arr_respuesta['data'] = $response;
        return $this->crearRespuesta($arr_respuesta);
    }
}
