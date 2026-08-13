<?php

namespace Modules\Internaltype\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;

class InternaltypesController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Internaltypes';

        // module name
        $this->module_name = 'internaltypes';

        // directory path of the module
        $this->module_path = 'internaltype::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Internaltype\Models\Internaltype";
    }

}
