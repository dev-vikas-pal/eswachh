<?php

namespace Modules\Smstemplate\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        /**
         * Backend
         */
        'Modules\Smstemplate\Events\Backend\NewCreated' => [
            'Modules\Smstemplate\Listeners\Backend\NewCreated\UpdateAllOnNewCreated',
        ],
        
        /**
         * Frontend
         */
    ];
}
