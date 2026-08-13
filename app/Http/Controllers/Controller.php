<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Modules\Smstemplate\Models\Smstemplate;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function getSmsTemplate($slug = '')
    {
        return Smstemplate::where('status', 1)->where('slug', $slug)->first();
    }
}
