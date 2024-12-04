<?php

namespace App\Providers\General;

use App\Events\Country\Process\ProcessGetConfCountriesEvent;
use App\Events\Invoice\Process\ProcessInvoiceCreateEvent;
use App\Events\Invoice\Process\ProcessValidateAffiliationGatewayEvent;
use App\Events\Invoice\Validation\ValidationInvoiceCreateEvent;
use App\Events\Invoice\Validation\ValidationValidateAffiliationGatewayEvent;
use App\Listeners\Country\Process\ProcessGetConfCountriesListener;
use App\Listeners\Invoice\Process\ProcessInvoiceCreateListener;
use App\Listeners\Invoice\Process\ProcessValidateAffiliationGatewayListener;
use App\Listeners\Invoice\Validation\ValidationInvoiceCreateListener;
use App\Listeners\Invoice\Validation\ValidationValidateAffiliationGatewayListener;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ProcessGetConfCountriesEvent::class => [
            ProcessGetConfCountriesListener::class,
        ],
        ValidationInvoiceCreateEvent::class => [
            ValidationInvoiceCreateListener::class,
        ],
        ProcessInvoiceCreateEvent::class => [
            ProcessInvoiceCreateListener::class,
        ],
        ValidationValidateAffiliationGatewayEvent::class => [
            ValidationValidateAffiliationGatewayListener::class,
        ],
        ProcessValidateAffiliationGatewayEvent::class => [
            ProcessValidateAffiliationGatewayListener::class,
        ]
    ];
}
