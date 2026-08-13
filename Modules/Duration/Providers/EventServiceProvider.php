<?php

namespace Modules\Duration\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        /**
         * Backend
         */
        'Modules\Duration\Events\Backend\NewCreated' => [
            'Modules\Duration\Listeners\Backend\NewCreated\UpdateAllOnNewCreated',
        ],
        
        /**
         * Frontend
         */
    ];
}
