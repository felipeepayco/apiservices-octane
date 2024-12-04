<?php

return [
    App\Providers\AppServiceProvider::class,
    //eControl
    App\Providers\Latam\EventServiceProvider::class,
    App\Providers\ShoppingcartDelivery\EventServiceProvider::class,
    App\Providers\General\EventServiceProvider::class,
    //Paycolink
    App\Providers\PaycoLink\PaycoLinkEventServiceProvider::class,
    //Buttons
    App\Providers\Buttons\ButtonEventServiceProvider::class,
    //Vende
    App\Providers\Vende\EventServiceProvider::class,

];
