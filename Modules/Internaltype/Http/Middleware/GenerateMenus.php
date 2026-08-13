<?php

namespace Modules\Internaltype\Http\Middleware;

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

                // Internaltypes
                $menu->add('<i class="nav-icon fa-regular fa-sun"></i> ' . __('Internaltypes'), [
                    'route' => 'backend.internaltypes.index',
                    'class' => 'nav-item',
                ])
                    ->data([
                        'order'         => 77,
                        'activematches' => ['admin/internaltypes*'],
                        'permission'    => ['view_internaltypes'],
                    ])
                    ->link->attr([
                        'class' => 'nav-link',
                    ]);
            })->sortBy('order');
        }

        return $next($request);
    }
}
