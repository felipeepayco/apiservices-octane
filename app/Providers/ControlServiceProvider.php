<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;


class ControlServiceProvider extends ServiceProvider
{

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        //eControl
        'App\Events\Econtrol\Process\ProcessCloneEcontrolRulesEvent' => [
            'App\Listeners\Econtrol\Process\ProcessCloneEcontrolRulesListener',
        ],
        'App\Events\Econtrol\Validation\ValidationWsConfigRulesEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationWsConfigRulesListener',
        ],
        'App\Events\Econtrol\Validation\ValidationWsConfigFiltersEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationWsConfigFiltersListener',
        ],
        'App\Events\Econtrol\Process\ProcessSetWsFilterConfigEvent' => [
            'App\Listeners\Econtrol\Process\ProcessSetWsFilterConfigListener',
        ],
        'App\Events\Econtrol\Validation\ValidationWsListSearchEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationWsListSearchListener',
        ],
        'App\Events\Econtrol\Process\ProcessWsListSearchEvent' => [
            'App\Listeners\Econtrol\Process\ProcessWsListSearchListener',
        ],
        'App\Events\Econtrol\Validation\ValidationWsCreateListEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationWsCreateListListener',
        ],
        'App\Events\Econtrol\Process\ProcessWsCreateListEvent' => [
            'App\Listeners\Econtrol\Process\ProcessWsCreateListListener',
        ],
        'App\Events\Econtrol\Validation\ValidationUpdateOrRemoveWsListEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationUpdateOrRemoveWsListListener',
        ],
        'App\Events\Econtrol\Process\ProcessConsultTransactionsRegistersEvent' => [
            'App\Listeners\Econtrol\Process\ProcessConsultTransactionsRegistersListener',
        ],
        'App\Events\Econtrol\Validation\ValidationDetailClientTransactionRegisterEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationDetailClientTransactionRegisterListener',
        ],
        'App\Events\Econtrol\Process\ProcessDetailClientTransactionRegisterEvent' => [
            'App\Listeners\Econtrol\Process\ProcessDetailClientTransactionRegisterListener',
        ],
        'App\Events\Econtrol\Validation\ValidationDetailClientTransactionNoteUpdateEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationDetailClientTransactionNoteUpdateListener',
        ],
        'App\Events\Econtrol\Process\ProcessDetailClientTransactionNoteUpdateEvent' => [
            'App\Listeners\Econtrol\Process\ProcessDetailClientTransactionNoteUpdateListener',
        ],
        'App\Events\Econtrol\Process\ProcessDetailClientTransactionNoteCreateEvent' => [
            'App\Listeners\Econtrol\Process\ProcessDetailClientTransactionNoteCreateListener',
        ],
        'App\Events\Econtrol\Validation\ValidationDetailClientTransactionNoteCreateEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationDetailClientTransactionNoteCreateListener',
        ],
        'App\Events\Econtrol\Validation\ValidationUploadDocumentEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationUploadDocumentListener',
        ],
        'App\Events\Econtrol\Process\ProcessUploadDocumentEvent' => [
            'App\Listeners\Econtrol\Process\ProcessUploadDocumentListener',
        ],
        'App\Events\Econtrol\Validation\ValidationAllowTransactionEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationAllowTransactionListener',
        ],
        'App\Events\Econtrol\Validation\ValidationDenyTransactionEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationDenyTransactionListener',
        ],
        'App\Events\Econtrol\Validation\ValidationVerificationTransactionEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationVerificationTransactionListener',
        ],
        'App\Events\Econtrol\Process\ProcessAllowTransactionEvent' => [
            'App\Listeners\Econtrol\Process\ProcessAllowTransactionListener',
        ],
        'App\Events\Econtrol\Process\ProcessDenyTransactionEvent' => [
            'App\Listeners\Econtrol\Process\ProcessDenyTransactionListener',
        ],
        'App\Events\Econtrol\Process\ProcessVerificationTransactionEvent' => [
            'App\Listeners\Econtrol\Process\ProcessVerificationTransactionListener',
        ],
        'App\Events\Econtrol\Validation\ValidationGeneralConsultFiltersEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationGeneralConsultFiltersListener',
        ],
        'App\Events\Econtrol\Process\ProcessUpdateOrRemoveWsListEvent' => [
            'App\Listeners\Econtrol\Process\ProcessUpdateOrRemoveWsListListener',
        ],
        'App\Events\Econtrol\Validation\ValidationFilterAcept3dsEvent' => [
            'App\Listeners\Econtrol\Validation\ValidationFilterAcept3dsListener',
        ],
        'App\Events\Econtrol\Process\ProcessFilterAcept3dsEvent' => [
            'App\Listeners\Econtrol\Process\ProcessFilterAcept3dsListener',
        ],
    ];
}