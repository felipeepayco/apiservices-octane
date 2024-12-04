<?php
namespace App\Providers\Buttons;


use App\Events\Buttons\Process\ConsultButtonDeleteEvent;
use App\Events\Buttons\Process\ConsultButtonShowEvent;
use App\Events\Buttons\Process\ConsultButtonListEvent;
use App\Events\Buttons\Process\ConsultSellNewButtonEvent;
use App\Events\Buttons\Validation\ValidationGeneralButtonDeleteEvent;
use App\Events\Buttons\Validation\ValidationGeneralButtonShowEvent;
use App\Events\Buttons\Validation\ValidationGeneralButtonListEvent;
use App\Events\Buttons\Validation\ValidationGeneralSellNewButtonEvent;
use App\Listeners\Buttons\Validation\ValidationGeneralButtonDeleteListener;
use App\Listeners\Buttons\Validation\ValidationGeneralButtonListListener;
use App\Listeners\Buttons\Validation\ValidationGeneralButtonShowListener;
use App\Listeners\Buttons\Validation\ValidationGeneralSellNewButtonListener;
use App\Listeners\Buttons\Process\ConsultButtonDeleteListener;
use App\Listeners\Buttons\Process\ConsultButtonShowListener;
use App\Listeners\Buttons\Process\ConsultButtonListListener;
use App\Listeners\Buttons\Process\ConsultSellNewButtonListener;
use Illuminate\Support\ServiceProvider;

class ButtonEventServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $listen = [
        ConsultSellNewButtonEvent::class => [
            ConsultSellNewButtonListener::class,
        ],
        ConsultButtonListEvent::class => [
            ConsultButtonListListener::class,
        ],
        ConsultButtonShowEvent::class => [
            ConsultButtonShowListener::class,
        ],
        ConsultButtonDeleteEvent::class => [
            ConsultButtonDeleteListener::class,
        ],
        ValidationGeneralSellNewButtonEvent::class => [
            ValidationGeneralSellNewButtonListener::class,
        ],
        ValidationGeneralButtonListEvent::class => [
            ValidationGeneralButtonListListener::class,
        ],
        ValidationGeneralButtonShowEvent::class => [
            ValidationGeneralButtonShowListener::class,
        ],
        ValidationGeneralButtonDeleteEvent::class => [
            ValidationGeneralButtonDeleteListener::class,
        ],
    ];
}
