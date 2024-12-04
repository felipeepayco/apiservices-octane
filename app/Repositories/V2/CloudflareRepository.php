<?php
namespace App\Repositories\V2;

class CloudflareRepository
{
    public function verifyToken()
    {
        $api = getenv('CLOUDFLARE_API');
        $token = getenv('CLOUDFLARE_TOKEN');

        $ch = curl_init("$api/client/v4/user/tokens/verify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                "Authorization: Bearer $token",
                'Content-Type:application/json',
            )
        );
        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response);

        return $json;

    }

    public function registerSubdomain($subdomain)
    {

        $correo = getenv('CLOUDFLARE_EMAIL');
        $clave_api = getenv('CLOUDFLARE_APIKEY');
        $zona_id = getenv('CLOUDFLARE_ZONE');

        $tipo_registro = 'CNAME';
        $contenido_registro = getenv('CLOUDFLARE_DOMAIN');
        $api = getenv('CLOUDFLARE_API');

        $url = "$api/client/v4/zones/{$zona_id}/dns_records";

        $data = array(
            'type' => $tipo_registro,
            'name' => $subdomain,
            'content' => $contenido_registro
        );
        $headers = array(
            "X-Auth-Email: $correo",
            "X-Auth-Key: $clave_api",
            "Content-Type: application/json"
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $response = json_decode($response);
        return $response;
    }

    public function consultationSubdomain($subdomain)
    {

        $correo = getenv('CLOUDFLARE_EMAIL');
        $clave_api = getenv('CLOUDFLARE_APIKEY');
        $zona_id = getenv('CLOUDFLARE_ZONE');
        $api = getenv('CLOUDFLARE_API');

        $url = "$api/client/v4/zones/{$zona_id}/dns_records";
        $headers = array(
            "X-Auth-Email: $correo",
            "X-Auth-Key: $clave_api",
            "Content-Type: application/json"
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200) {
            $dnsRecords = json_decode($response, true);
    
            foreach ($dnsRecords['result'] as $record) {
                if ($record['type'] == 'CNAME' && $record['name'] == $subdomain.'.'.$record['zone_name']) {
                    return ["success" => true, "result" => $record];
                }
            }
        }
        $response = json_decode($response);
        return ["success" => false, "result" => $response];
    }

    public function deleteSubdomain($idRegistro)
    {
        $correo = getenv('CLOUDFLARE_EMAIL');
        $clave_api = getenv('CLOUDFLARE_APIKEY');
        $zona_id = getenv('CLOUDFLARE_ZONE');
        $api = getenv('CLOUDFLARE_API');

        $url = "$api/client/v4/zones/{$zona_id}/dns_records/{$idRegistro}";

        $headers = array(
            "X-Auth-Email: $correo",
            "X-Auth-Key: $clave_api",
            "Content-Type: application/json"
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($response);
        
        return $httpCode == 200 && $response->success;
    }
}