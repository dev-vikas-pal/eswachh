<?php

namespace Modules\Report\Http\Middleware;

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
            // Reports
            $menu->add('<i class="nav-icon fa-regular fa-sun"></i> '.__('Cloth Reports'), [
                'route' => 'backend.reports.clothReport',
                'class' => 'nav-item',
            ])
            ->data([
                'order'         => 3,
                'activematches' => ['admin/reports*'],
                'permission'    => ['view_reports'],
            ])
            ->link->attr([
                'class' => 'nav-link',
            ]);
        })->sortBy('order');

        return $next($request);
    }
}
