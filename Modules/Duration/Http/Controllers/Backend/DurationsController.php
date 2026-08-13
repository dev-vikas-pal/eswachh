<?php

namespace Modules\Duration\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;

class DurationsController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Durations';

        // module name
        $this->module_name = 'durations';

        // directory path of the module
        $this->module_path = 'duration::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Duration\Models\Duration";
    }

}
