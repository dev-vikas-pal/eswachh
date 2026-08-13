<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Modules\Package\Models\Package;
use Modules\Duration\Models\Duration;

class FrontendController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $packages = Package::get();
        $durations = Duration::get();
       // echo "<pre>";print_r($packages);die;
        return view('frontend.index', compact('packages','durations'));
    }

    /**
     * Privacy Policy Page.
     *
     * @return \Illuminate\Http\Response
     */
    public function privacy()
    {
        return view('frontend.privacy');
    }

    /**
     * Terms & Conditions Page.
     *
     * @return \Illuminate\Http\Response
     */
    public function terms()
    {
        return view('frontend.terms');
    }
}
