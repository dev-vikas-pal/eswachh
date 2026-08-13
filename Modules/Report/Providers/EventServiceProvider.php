<?php

namespace Modules\Report\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        /**
         * Backend
         */
        'Modules\Report\Events\Backend\NewCreated' => [
            'Modules\Report\Listeners\Backend\NewCreated\UpdateAllOnNewCreated',
        ],
        
        /**
         * Frontend
         */
    ];
}
