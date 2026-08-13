<?php

namespace Modules\Team\Http\Middleware;

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

            // Teams
            $menu->add('<i class="nav-icon fa-regular fa-sun"></i> '.__('Teams'), [
                'route' => 'backend.teams.index',
                'class' => 'nav-item',
            ])
            ->data([
                'order'         => 77,
                'activematches' => ['admin/teams*'],
                'permission'    => ['view_teams'],
            ])
            ->link->attr([
                'class' => 'nav-link',
            ]);
        })->sortBy('order');

        return $next($request);
    }
}
