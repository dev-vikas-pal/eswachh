<?php

namespace Modules\Society\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        /**
         * Backend
         */
        'Modules\Society\Events\Backend\NewCreated' => [
            'Modules\Society\Listeners\Backend\NewCreated\UpdateAllOnNewCreated',
        ],
        
        /**
         * Frontend
         */
    ];
}
