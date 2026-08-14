<?php

namespace Modules\Report\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use App\Services\SectorService;
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
        // The cleaner name is joined in rather than looked up once per row.
        $clothQuery = DB::table('cloth_services')
            ->leftJoin('users', 'users.id', '=', 'cloth_services.created_by')
            ->select('cloth_services.*', 'users.name as cleaner_name');

        $request = request()->all();

        // Cloth services belong to the sector of the order they were done for.
        $sectorIds = SectorService::selectedSectorIds($request['filter_sector_id'] ?? null);

        if ($sectorIds !== null) {
            $clothQuery = $clothQuery->whereIn('cloth_services.order_id', function ($sub) use ($sectorIds) {
                $sub->select('id')->from('orders')->whereIn('sector_id', $sectorIds);
            });
        }

        // Column names are qualified: users is joined in and shares several of
        // these column names with cloth_services.
        if (!empty($request['filter_status']) && $request['filter_status'] != '*') {
            $clothQuery = $clothQuery->where('cloth_services.status', $request['filter_status']);
        }
        if (!empty($request['created_by']) && $request['created_by'] != '*') {
            $clothQuery = $clothQuery->where('cloth_services.created_by', $request['created_by']);
        }
        if (!empty($request['filter_cloth_count']) && $request['filter_cloth_count'] != '*') {
            $clothQuery = $clothQuery->where('cloth_services.cloth_count', $request['filter_cloth_count']);
        }
        if (!empty($request['filter_date']) && $request['filter_date'] != '*') {
            $clothQuery = $clothQuery->whereDate('cloth_services.date', $request['filter_date']);
        }
        $clothOrders = $clothQuery->get();
        return Datatables::of($clothOrders)
            ->editColumn('date', function ($data) {
                return Carbon::parse($data->date)->format('d-m-Y');
            })
            ->editColumn('cleaner_name', function ($data) {
                return $data->cleaner_name ?? 'NA';
            })
            ->rawColumns(['name', 'action'])
            ->make(true);
    }
}
