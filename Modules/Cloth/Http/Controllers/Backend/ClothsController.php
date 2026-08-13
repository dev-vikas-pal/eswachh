<?php

namespace Modules\Cloth\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;

class ClothsController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Cloths';

        // module name
        $this->module_name = 'cloths';

        // directory path of the module
        $this->module_path = 'cloth::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Cloth\Models\Cloth";
    }

}
