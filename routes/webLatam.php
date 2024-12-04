<?php

$router->group(
    ['middleware' => 'jwt.auth'],
    function () use ($router) {
       
    }
);