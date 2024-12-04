<?php

namespace App\Helpers\Validation;

function imageExists($imageUrl) {
    // Usa la función get_headers para comprobar si la imagen está disponible
    $headers = @get_headers(str_replace(" ", "%20", $imageUrl));
    return !empty($headers) && strpos($headers[0], '200') !== false;
}

class ValidateUrlImage
{
    public static function locateImage($imagePath) {
        // Comprueba si la imagen existe en el primer dominio
        $imageUrl1 = config("app.AWS_BASE_PUBLIC_URL_SHOPS") . '/' . $imagePath;
        if (imageExists($imageUrl1)) {
            return $imageUrl1;
        }

        // Comprueba si la imagen existe en el segundo dominio
        $imageUrl2 = getenv("AWS_BASE_PUBLIC_URL") . '/' . $imagePath;
        if (imageExists($imageUrl2)) {
            return $imageUrl2;
        }

        return $imageUrl1;
    }


    public static function getDomainFromUrl($imageUrl) {
        if (strpos($imageUrl, config("app.AWS_BASE_PUBLIC_URL_SHOPS")) !== false) {
            return config("app.AWS_BASE_PUBLIC_URL_SHOPS");
        } elseif (strpos($imageUrl, getenv("AWS_BASE_PUBLIC_URL")) !== false) {
            return getenv("AWS_BASE_PUBLIC_URL");
        }
        return "";
    }
}
