<?php

namespace Modules\Customer\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use App\Services\SectorService;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomersController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Users';

        // module name
        $this->module_name = 'customers';

        // directory path of the module
        $this->module_path = 'customer::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Customer\Models\Customer";
    }
    public function index_data()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'List';

        // Address is joined in rather than looked up per row, which also
        // removes the crash when a user has no profile row.
        // Roles are rendered for every row, so they are loaded in one query.
        $$module_name = User::with('roles')
            ->leftJoin('userprofiles', 'userprofiles.user_id', '=', 'users.id')
            ->leftJoin('societies', 'societies.id', '=', 'userprofiles.society_id')
            ->select(
                'users.id',
                'users.name',
                'users.username',
                'users.email',
                'users.email_verified_at',
                'users.updated_at',
                'users.status',
                'userprofiles.house_no',
                'societies.name as society_name'
            );
        $request = request()->all();
        if (!empty($request['user_type']) && $request['user_type'] != '*') {
            $$module_name->whereHas('roles', function ($query) use ($request) {
                if (!empty($request['user_type'])) {
                    $query->where('name', '=', $request['user_type']);
                }
            });
        }

        // A Franchise Owner only sees the people in their own sectors.
        $$module_name = SectorService::scopeByUserSector(
            $$module_name,
            SectorService::selectedSectorIds($request['filter_sector_id'] ?? null),
            'users.id'
        );

        $data = $$module_name;

        return Datatables::of($$module_name)
            ->addColumn('action', function ($data) {
                $module_name = $this->module_name;

                return view('backend.includes.user_actions', compact('module_name', 'data'));
            })
            ->addColumn('user_roles', function ($data) {
                $module_name = 'users';
                return view('backend.includes.user_roles', compact('module_name', 'data'));
            })
            ->editColumn('name', '<strong>{{$name}}</strong>')
            ->editColumn('status', function ($data) {
                $return_data = $data->status_label;
                $return_data .= '<br>' . $data->confirmed_label;

                return $return_data;
            })->editColumn('address', function ($data) {
                return $data->society_name ? "{$data->society_name}, {$data->house_no}" : '';
            })
            ->editColumn('updated_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('LLLL');
                }
            })
            ->rawColumns(['name', 'action', 'status', 'user_roles'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }
}
