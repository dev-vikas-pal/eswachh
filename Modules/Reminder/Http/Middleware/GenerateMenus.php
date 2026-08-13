<?php

namespace Modules\Reminder\Http\Middleware;

use Closure;

class GenerateMenus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /*
         *
         * Module Menu for Admin Backend
         *
         * *********************************************************************
         */
        \Menu::make('admin_sidebar', function ($menu) {

            // Reminders
            $menu->add('<i class="nav-icon fa-regular fa-sun"></i> '.__('Reminders'), [
                'route' => 'backend.reminders.index',
                'class' => 'nav-item',
            ])
            ->data([
                'order'         => 6,
                'activematches' => ['admin/reminders*'],
                'permission'    => ['view_reminders'],
            ])
            ->link->attr([
                'class' => 'nav-link',
            ]);
        })->sortBy('order');

        return $next($request);
    }
}
