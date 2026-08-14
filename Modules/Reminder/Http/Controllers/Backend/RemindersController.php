<?php

namespace Modules\Reminder\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use App\Services\SectorService;
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

        // A reminder belongs to the sector of the person it is assigned to.
        $$module_name = SectorService::scopeByUserSector(
            $module_model::select(),
            SectorService::allowedSectorIds(),
            'reminders.assigned_user_id'
        );

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

    /**
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $sectorIds = SectorService::allowedSectorIds();

        if ($sectorIds !== null) {
            $accessible = SectorService::scopeByUserSector(
                $this->module_model::where('id', $id),
                $sectorIds,
                'reminders.assigned_user_id'
            )->exists();

            if (! $accessible) {
                abort(404);
            }
        }

        return parent::show($id);
    }
}
