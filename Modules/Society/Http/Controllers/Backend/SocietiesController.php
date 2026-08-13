<?php

namespace Modules\Society\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;

class SocietiesController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Societies';

        // module name
        $this->module_name = 'societies';

        // directory path of the module
        $this->module_path = 'society::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Society\Models\Society";
    }

}
