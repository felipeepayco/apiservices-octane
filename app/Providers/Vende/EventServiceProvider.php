<?php

namespace App\Providers\Vende;

use Illuminate\Support\ServiceProvider;
use App\Events\Vende\Validation\ValidationShowConfigurationCatalogueEvent;
use App\Events\Vende\Process\ProcessShowConfigurationCatalogueEvent;
use App\Events\Vende\Process\ProcessConfigurationBabiloniaEvent;
use App\Events\Vende\Process\ProcessVendePlanEvent;
use App\Events\Vende\Process\ShowConfigurationDeliveryEvent;

use App\Listeners\Vende\Validation\ValidationShowConfigurationCatalogueListener;
use App\Listeners\Vende\Process\ProcessShowConfigurationCatalogueListener;
use App\Listeners\Vende\Process\ProcessConfigurationBabiloniaListener;
use App\Listeners\Vende\Process\ProcessVendePlanListener;
use App\Listeners\Vende\Process\ShowConfigurationDeliveryListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ValidationShowConfigurationCatalogueEvent::class => [
            ValidationShowConfigurationCatalogueListener::class,
        ],
        ProcessShowConfigurationCatalogueEvent::class => [
            ProcessShowConfigurationCatalogueListener::class,
        ],
        ProcessConfigurationBabiloniaEvent::class => [
            ProcessConfigurationBabiloniaListener::class,
        ],
        ProcessVendePlanEvent::class => [
            ProcessVendePlanListener::class,
        ],

        ShowConfigurationDeliveryEvent::class => [
            ShowConfigurationDeliveryListener::class,
        ],
    ];
}
