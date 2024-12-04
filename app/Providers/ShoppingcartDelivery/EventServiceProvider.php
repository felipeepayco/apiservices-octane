<?php

namespace App\Providers\ShoppingcartDelivery;

use Illuminate\Support\ServiceProvider;
use App\Listeners\ShoppingCart\Validation\ValidationChangeStateDeliveryListener;
use App\Events\ShoppingCart\Validation\ValidationChangeStateDeliveryEvent;
use App\Events\ShoppingCart\Process\ProcessChangeStateDeliveryEvent;
use App\Listeners\ShoppingCart\Process\ProcessChangeStateDeliveryListener;
use App\Listeners\ShoppingCart\Validation\ValidationLoadPickupListener;
use App\Events\ShoppingCart\Validation\ValidationLoadPickupEvent;
use App\Events\ShoppingCart\Process\ProcessLoadPickupEvent;
use App\Listeners\ShoppingCart\Process\ProcessLoadPickupListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ValidationChangeStateDeliveryEvent::class => [
            ValidationChangeStateDeliveryListener::class,
        ],
        ProcessChangeStateDeliveryEvent::class => [
            ProcessChangeStateDeliveryListener::class,
        ],
        ValidationLoadPickupEvent::class => [
            ValidationLoadPickupListener::class,
        ],
        ProcessLoadPickupEvent::class => [
            ProcessLoadPickupListener::class,
        ],
    ];
}
