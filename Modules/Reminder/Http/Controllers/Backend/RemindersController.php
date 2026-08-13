<?php

namespace Modules\Reminder\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use Yajra\DataTables\DataTables;
use App\Models\User;
use Carbon\Carbon;

class RemindersController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Reminders';

        // module name
        $this->module_name = 'reminders';

        // directory path of the module
        $this->module_path = 'reminder::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Reminder\Models\Reminder";
    }
    public function index_data()
    {
        $module_name = $this->module_name;
        $module_model = $this->module_model;
        $$module_name = $module_model::select();
        return Datatables::of($$module_name)
            ->addColumn('action', function ($data) {
                $module_name = $this->module_name;
                return view('backend.includes.action_column', compact('module_name', 'data'));
            })
            ->editColumn('created_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->created_at);
                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->editColumn('user_name', function ($data) {
                return User::find($data->assigned_user_id)->name ?? 'Not Assigned';
            })
            ->rawColumns(['name', 'action'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

}
