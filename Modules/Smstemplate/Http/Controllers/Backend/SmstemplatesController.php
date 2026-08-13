<?php

namespace Modules\Smstemplate\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\SMSService;
use Modules\Order\Models\Order;

class SmstemplatesController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Smstemplates';

        // module name
        $this->module_name = 'smstemplates';

        // directory path of the module
        $this->module_path = 'smstemplate::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Smstemplate\Models\Smstemplate";
    }
    public function store(Request $request)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Store';

        $$module_name_singular = $module_model::create($request->all());

        $ordersQuery = Order::with('user');
        if (!empty($request->status)) {
            $ordersQuery->where('status', $request->status);
        }
        $orders = $ordersQuery->get();
        foreach ($orders as $res) {
            if ($res->user && !empty($res->user->mobile)) {
                SMSService::sendWhatsAppMsg($res->user->mobile, $request->name);
            }
        }
        flash(icon() . "New '" . Str::singular($module_title) . "' Added")->success()->important();

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);

        return redirect("admin/$module_name");
    }
    public function update(Request $request, $id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Update';

        $$module_name_singular = $module_model::findOrFail($id);

        $$module_name_singular->update($request->all());

        $ordersQuery = Order::with('user');
        if (!empty($request->status)) {
            $ordersQuery->where('status', $request->status);
        }
        $orders = $ordersQuery->get();
        foreach ($orders as $res) {
            if ($res->user && !empty($res->user->mobile)) {
                SMSService::sendWhatsAppMsg($res->user->mobile, $request->name);
            }
        }

        flash(icon() . ' ' . Str::singular($module_title) . "' Updated Successfully")->success()->important();

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);

        return redirect()->route("backend.$module_name.show", $$module_name_singular->id);
    }
}
