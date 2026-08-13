<?php

namespace Modules\Internaltype\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        /**
         * Backend
         */
        'Modules\Internaltype\Events\Backend\NewCreated' => [
            'Modules\Internaltype\Listeners\Backend\NewCreated\UpdateAllOnNewCreated',
        ],
        
        /**
         * Frontend
         */
    ];
}
