<?php
namespace App\Providers\PaycoLink;


use App\Events\PaycoLink\Process\ProcessPaycoLinkPaymentCreateEvent;
use App\Events\PaycoLink\Validation\ValidationPaycoLinkPaymentCreateEvent;
use App\Listeners\PaycoLink\Process\ProcessPaycoLinkPaymentCreateListener;
use App\Listeners\PaycoLink\Validation\ValidationPaycoLinkPaymentCreateListener;
use Illuminate\Support\ServiceProvider;

class PaycoLinkEventServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $listen = [
        ValidationPaycoLinkPaymentCreateEvent::class => [
            ValidationPaycoLinkPaymentCreateListener::class,
        ],
        ProcessPaycoLinkPaymentCreateEvent::class => [
            ProcessPaycoLinkPaymentCreateListener::class,
        ],
    ];
}
