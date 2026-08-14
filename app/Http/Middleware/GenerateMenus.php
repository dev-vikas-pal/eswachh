<?php

namespace App\Http\Middleware;

use App\Services\SectorService;
use Closure;

class GenerateMenus
{
    /**
     * Master data screens a Franchise Owner must not navigate to. They still
     * hold the matching view permissions because the order form's dropdowns
     * read from these endpoints - this hides the navigation, not the data.
     *
     * @var array<int, string>
     */
    private const MASTER_MENU_PATTERNS = [
        'admin/carcategories*',
        'admin/cars*',
        'admin/cloths*',
        'admin/durations*',
        'admin/internaltypes*',
        'admin/packages*',
        'admin/sectors*',
        'admin/societies*',
        'admin/smstemplates*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        \Menu::make('admin_sidebar', function ($menu) {
            // Dashboard
            $menu->add('<i class="nav-icon fa-solid fa-cubes"></i> ' . __('Dashboard'), [
                'route' => 'backend.dashboard',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 1,
                    'activematches' => 'admin/dashboard*',
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);
            $user = auth()->user();
            $roles = !empty($user) ? $user->roles()->pluck('name')[0] : '';
            if ($roles == 'cleaner' || $roles == 'super admin') {
                $menu->add('<i class="nav-icon fa-solid fa-cubes"></i> ' . __('Today Task'), [
                    'route' => 'backend.cleaners.index',
                    'class' => 'nav-item',
                ])
                    ->data([
                        'order' => 1,
                        'activematches' => 'admin/cleaners',
                    ])
                    ->link->attr([
                        'class' => 'nav-link',
                    ]);
                
                $menu->add('<i class="nav-icon fa-solid fa-cubes"></i> ' . __('Cloth Pickup'), [
                    'route' => 'backend.cleaners.clothPickup',
                    'class' => 'nav-item',
                ])
                    ->data([
                        'order' => 4,
                        'activematches' => 'admin/cleaners',
                    ])
                    ->link->attr([
                        'class' => 'nav-link',
                    ]);
                
                $menu->add('<i class="nav-icon fa-solid fa-cubes"></i> ' . __('Cloth Delivery'), [
                    'route' => 'backend.cleaners.clothDelivery',
                    'class' => 'nav-item',
                ])
                    ->data([
                        'order' => 5,
                        'activematches' => 'admin/cleaners',
                    ])
                    ->link->attr([
                        'class' => 'nav-link',
                    ]);
            }

            // Complaints. Customers and cleaners reach this too, which is why
            // it sits above the sector reporting entries.
            $menu->add('<i class="nav-icon fa-solid fa-comment-dots"></i> '.__('Complaints'), [
                'route' => 'backend.complaints.index',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 3,
                    'activematches' => ['admin/complaints*'],
                    'permission' => ['view_complaints'],
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            $menu->add('<i class="nav-icon fa-solid fa-clipboard-check"></i> '.__('Attendance'), [
                'route' => 'backend.attendances.index',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 5,
                    'activematches' => ['admin/attendances*'],
                    'permission' => ['view_attendances'],
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            // Payments
            $menu->add('<i class="nav-icon fa-solid fa-indian-rupee-sign"></i> '.__('Payments'), [
                'route' => 'backend.payments.index',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 6,
                    'activematches' => ['admin/payments'],
                    'permission' => ['view_payments'],
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            $menu->add('<i class="nav-icon fa-solid fa-chart-column"></i> '.__('Payment Reports'), [
                'route' => 'backend.payments.reports',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 7,
                    'activematches' => ['admin/payments/reports'],
                    'permission' => ['view_payments'],
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            // Notifications
            // $menu->add('<i class="nav-icon fas fa-bell"></i> Notifications', [
            //     'route' => 'backend.notifications.index',
            //     'class' => 'nav-item',
            // ])
            //     ->data([
            //         'order' => 99,
            //         'activematches' => 'admin/notifications*',
            //         'permission' => [],
            //     ])
            //     ->link->attr([
            //         'class' => 'nav-link',
            //     ]);

            // Separator: Access Management
                $menu->add('Management', [
                    'class' => 'nav-title',
                ])
                    ->data([
                        'order' => 101,
                        'permission' => ['edit_settings', 'view_backups', 'view_users', 'view_roles', 'view_logs'],
                    ]);

            // Settings
            $menu->add('<i class="nav-icon fas fa-cogs"></i> Settings', [
                'route' => 'backend.settings',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 102,
                    'activematches' => 'admin/settings*',
                    'permission' => ['edit_settings'],
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            // Backup
            // $menu->add('<i class="nav-icon fas fa-archive"></i> Backups', [
            //     'route' => 'backend.backups.index',
            //     'class' => 'nav-item',
            // ])
            //     ->data([
            //         'order' => 103,
            //         'activematches' => 'admin/backups*',
            //         'permission' => ['view_backups'],
            //     ])
            //     ->link->attr([
            //         'class' => 'nav-link',
            //     ]);

            // Access Control Dropdown
            $accessControl = $menu->add('<i class="nav-icon fa-solid fa-user-gear"></i> Access Control', [
                'class' => 'nav-group',
            ])
                ->data([
                    'order' => 104,
                    'activematches' => [
                        'admin/users*',
                        'admin/roles*',
                    ],
                    'permission' => ['view_users', 'view_roles'],
                ]);
            $accessControl->link->attr([
                'class' => 'nav-link nav-group-toggle',
                'href' => '#',
            ]);

            // Submenu: Users
            $accessControl->add('<i class="nav-icon fa-solid fa-user-group"></i> Users', [
                'route' => 'backend.users.index',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 105,
                    'activematches' => 'admin/users*',
                    'permission' => ['view_users'],
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            // Submenu: Roles
            $accessControl->add('<i class="nav-icon fa-solid fa-user-shield"></i> Roles', [
                'route' => 'backend.roles.index',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 106,
                    'activematches' => 'admin/roles*',
                    'permission' => ['view_roles'],
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            // Log Viewer
            // Log Viewer Dropdown
            $accessControl = $menu->add('<i class="nav-icon fa-solid fa-list-check"></i> Log Viewer', [
                'class' => 'nav-group',
            ])
                ->data([
                    'order' => 107,
                    'activematches' => [
                        'log-viewer*',
                    ],
                    'permission' => ['view_logs'],
                ]);
            $accessControl->link->attr([
                'class' => 'nav-link nav-group-toggle',
                'href' => '#',
            ]);

            // Submenu: Log Viewer Dashboard
            $accessControl->add('<i class="nav-icon fa-solid fa-list"></i> Dashboard', [
                'route' => 'log-viewer::dashboard',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 108,
                    'activematches' => 'admin/log-viewer',
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            //Submenu: Log Viewer Logs by Days
            $accessControl->add('<i class="nav-icon fa-solid fa-list-ol"></i> Logs by Days', [
                'route' => 'log-viewer::logs.list',
                'class' => 'nav-item',
            ])
                ->data([
                    'order' => 109,
                    'activematches' => 'admin/log-viewer/logs*',
                ])
                ->link->attr([
                    'class' => 'nav-link',
                ]);

            // Manage Masters is hidden from Franchise Owners. They hold the
            // master view permissions only so the order form's dropdowns work.
            if (SectorService::isFranchiseOwner()) {
                $menu->filter(function ($item) {
                    $activematches = $item->data('activematches');

                    if (empty($activematches)) {
                        return true;
                    }

                    $activematches = is_string($activematches) ? [$activematches] : $activematches;

                    return empty(array_intersect($activematches, self::MASTER_MENU_PATTERNS));
                });
            }

            // Access Permission Check
            $menu->filter(function ($item) {
                if ($item->data('permission')) {
                    if (auth()->check()) {
                        if (auth()->user()->hasRole('super admin')) {
                            return true;
                        } elseif (auth()->user()->hasAnyPermission($item->data('permission'))) {
                            return true;
                        }
                    }
                    return false;
                } else {
                    return true;
                }
            });

            // Set Active Menu
            $menu->filter(function ($item) {
                if ($item->activematches) {
                    $activematches = (is_string($item->activematches)) ? [$item->activematches] : $item->activematches;
                    foreach ($activematches as $pattern) {
                        if (request()->is($pattern)) {
                            $item->active();
                            $item->link->active();
                            if ($item->hasParent()) {
                                $item->parent()->active();
                            }
                        }
                    }
                }

                return true;
            });
        })->sortBy('order');

        return $next($request);
    }
}
