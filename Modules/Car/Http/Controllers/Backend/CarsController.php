<?php

namespace Modules\Car\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\CarCategory\Models\CarCategory;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class CarsController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Cars';

        // module name
        $this->module_name = 'cars';

        // directory path of the module
        $this->module_path = 'car::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Car\Models\Car";
    }
    public function create()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Create';

        $categories = CarCategory::pluck('name', 'id');

        Log::info(label_case($module_title.' '.$module_action).' | User:'.Auth::user()->name.'(ID:'.Auth::user()->id.')');

        return view(
            "car::backend.$module_name.create",
            compact('module_title', 'module_name','module_path', 'module_icon', 'module_action', 'module_name_singular', 'categories')
        );
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

        $$module_name = $module_model::select('id', 'name', 'category_id', 'status');

        $data = $$module_name;

        return Datatables::of($$module_name)
            ->editColumn('category', function ($data) {
                return CarCategory::find($data->category_id)->name ?? '';
            })
            ->addColumn('action', function ($data) {
                $module_name = $this->module_name;
                return view('backend.includes.action_column', compact('module_name', 'data'));
            })
            ->rawColumns(['name', 'status', 'action'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }
    public function edit($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Edit';

        $$module_name_singular = $module_model::findOrFail($id);

        $categories = CarCategory::pluck('name', 'id');

        Log::info(label_case($module_title.' '.$module_action)." | '".$$module_name_singular->name.'(ID:'.$$module_name_singular->id.") ' by User:".Auth::user()->name.'(ID:'.Auth::user()->id.')');

        return view(
            "car::backend.$module_name.edit",
            compact('categories', 'module_title','module_path', 'module_name', 'module_icon', 'module_name_singular', 'module_action', "$module_name_singular")
        );
    }
}
