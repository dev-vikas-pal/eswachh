<?php

namespace Modules\Customer\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
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

        $$module_name = User::select('id', 'name', 'username', 'email', 'email_verified_at', 'updated_at', 'status');
        $request = request()->all();
        if (!empty($request['user_type']) && $request['user_type'] != '*') {
            $$module_name->whereHas('roles', function ($query) use ($request) {
                if (!empty($request['user_type'])) {
                    $query->where('name', '=', $request['user_type']);
                }
            });
        }
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
                $userprofile = DB::table('userprofiles')->where('user_id', $data->id)->first();
                $location = DB::table('cities')
                ->join('areas', 'areas.city_id', '=', 'cities.id')
                ->join('sectors', 'sectors.area_id', '=', 'areas.id')
                ->join('societies', 'societies.sector_id', '=', 'sectors.id')
                ->where([
                    ['cities.id', $userprofile->city_id],
                    ['areas.id', $userprofile->area_id],
                    ['sectors.id', $userprofile->sector_id],
                    ['societies.id', $userprofile->society_id],
                ])
                ->select('cities.name as city_name', 'areas.name as area_name', 'sectors.name as sector_name', 'societies.name as society_name')
                ->first();
              //  return $location ? "{$location->city_name}, {$location->area_name}, {$location->sector_name}, {$location->society_name}, {$userprofile->house_no}" : '';
                return $location ? "{$location->society_name}, {$userprofile->house_no}" : '';
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
