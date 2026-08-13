<?php

namespace Modules\Package\Http\Middleware;

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
        $user = auth()->user();
        $roles = !empty($user)?$user->roles()->pluck('name')[0]:'';
        if ($roles == 'super admin') {
            \Menu::make('admin_sidebar', function ($menu) {

                // Packages
                $menu->add('<i class="nav-icon fa-regular fa-sun"></i> ' . __('Packages Manage'), [
                    'route' => 'backend.packages.index',
                    'class' => 'nav-item',
                ])
                    ->data([
                        'order'         => 77,
                        'activematches' => ['admin/packages*'],
                        'permission'    => ['view_packages'],
                    ])
                    ->link->attr([
                        'class' => 'nav-link',
                    ]);
            })->sortBy('order');
        }
        return $next($request);
    }
}
