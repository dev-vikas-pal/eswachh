<?php

namespace Modules\CarCategory\Http\Middleware;

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
        $roles = !empty($user) ? $user->roles()->pluck('name')[0] : '';
        if ($roles == 'super admin') {
        \Menu::make('admin_sidebar', function ($menu) {

            $menu->add('Manage Masters', [
                'class' => 'nav-title',
            ])
                ->data([
                    'order' => 9,
                    'permission' => [],
                ]);
            // CarCategories
            $menu->add('<i class="nav-icon fa-regular fa-sun"></i> '.__('CarCategories Manage'), [
                'route' => 'backend.carcategories.index',
                'class' => 'nav-item',
            ])
            ->data([
                'order'         => 10,
                'activematches' => ['admin/carcategories*'],
                'permission'    => ['view_carcategories'],
            ])
            ->link->attr([
                'class' => 'nav-link',
            ]);
        })->sortBy('order');
        }
        return $next($request);
    }
}
