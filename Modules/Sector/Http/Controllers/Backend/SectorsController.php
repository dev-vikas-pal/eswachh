<?php

namespace Modules\Sector\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;

class SectorsController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Sectors';

        // module name
        $this->module_name = 'sectors';

        // directory path of the module
        $this->module_path = 'sector::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Sector\Models\Sector";
    }

}
