<?php

namespace Modules\Report\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\User;

class ReportsController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Reports';

        // module name
        $this->module_name = 'reports';

        // directory path of the module
        $this->module_path = 'report::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Report\Models\Report";
    }
    public function clothReport()
    {
        $module_title = 'Cloths Report';
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_action = 'Cloths Report';
        $module_name_singular = Str::singular($module_name);

        $$module_name = '';

        logUserAccess($module_title . ' ' . $module_action);

        return view(
            "$module_path.$module_name.cloth_report_datatable",
            compact('module_title', 'module_name', "$module_name", 'module_icon', 'module_name_singular', 'module_action')
        );
    }
    public function clothReportData()
    {
        $clothQuery = DB::table('cloth_services')->select();

        $request = request()->all();

        if (!empty($request['filter_status']) && $request['filter_status'] != '*') {
            $clothQuery = $clothQuery->where('status', $request['filter_status']);
        }
        if (!empty($request['filter_package_id']) && $request['filter_package_id'] != '*') {
            $clothQuery = $clothQuery->where('package_id', $request['filter_package_id']);
        }
        if (!empty($request['created_by']) && $request['created_by'] != '*') {
            $clothQuery = $clothQuery->where('created_by', $request['created_by']);
        }
        if (!empty($request['filter_cloth_count']) && $request['filter_cloth_count'] != '*') {
            $clothQuery = $clothQuery->where('cloth_count', $request['filter_cloth_count']);
        }
        if (!empty($request['filter_date']) && $request['filter_date'] != '*') {
            $clothQuery = $clothQuery->whereDate('date', $request['filter_date']);
        }
        $clothOrders = $clothQuery->get();
        return Datatables::of($clothOrders)
            ->editColumn('date', function ($data) {
                return Carbon::parse($data->date)->format('d-m-Y');
            })
            ->editColumn('cleaner_name', function ($data) {
                return User::find($data->created_by)->name??'NA';
            })
            ->rawColumns(['name', 'action'])
            ->make(true);
    }
}
