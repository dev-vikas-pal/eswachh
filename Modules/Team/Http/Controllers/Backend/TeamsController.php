<?php

namespace Modules\Team\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamsController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Teams';

        // module name
        $this->module_name = 'teams';

        // directory path of the module
        $this->module_path = 'team::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Team\Models\Team";
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

        $$module_name_singular = $module_model::create($request->except('image'));

        if ($request->image) {
            $media = $$module_name_singular->addMedia($request->file('image'))->toMediaCollection($module_name);
            $$module_name_singular->image = $media->getUrl();
            $$module_name_singular->save();
        }

        flash(icon()."New '".Str::singular($module_title)."' Added")->success()->important();

        logUserAccess($module_title.' '.$module_action.' | Id: '.$$module_name_singular->id);

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

        $$module_name_singular->update($request->except('image', 'image_remove'));

        // Image
        if ($request->hasFile('image')) {
            if ($$module_name_singular->getMedia($module_name)->first()) {
                $$module_name_singular->getMedia($module_name)->first()->delete();
            }
            $media = $$module_name_singular->addMedia($request->file('image'))->toMediaCollection($module_name);

            $$module_name_singular->image = $media->getUrl();

            $$module_name_singular->save();
        }
        if ($request->image_remove == 'image_remove') {
            if ($$module_name_singular->getMedia($module_name)->first()) {
                $$module_name_singular->getMedia($module_name)->first()->delete();

                $$module_name_singular->image = '';

                $$module_name_singular->save();
            }
        }

        flash(icon().' '.Str::singular($module_title)."' Updated Successfully")->success()->important();

        logUserAccess($module_title.' '.$module_action.' | Id: '.$$module_name_singular->id);

        return redirect()->route("backend.$module_name.show", $$module_name_singular->id);
    }

}
