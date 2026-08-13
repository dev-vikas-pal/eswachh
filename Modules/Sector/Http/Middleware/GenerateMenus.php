<?php

namespace Modules\Sector\Http\Middleware;

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

            // Sectors
            $menu->add('<i class="nav-icon fa-regular fa-sun"></i> '.__('Sectors'), [
                'route' => 'backend.sectors.index',
                'class' => 'nav-item',
            ])
            ->data([
                'order'         => 77,
                'activematches' => ['admin/sectors*'],
                'permission'    => ['view_sectors'],
            ])
            ->link->attr([
                'class' => 'nav-link',
            ]);
        })->sortBy('order');

        return $next($request);
    }
}
