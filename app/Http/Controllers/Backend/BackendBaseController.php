<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use App\Models\User;
use App\Services\SMSService;
use Modules\Order\Models\Order;
use Illuminate\Support\Facades\Hash;

class BackendBaseController extends Controller
{
    use Authorizable;

    public $module_title;

    public $module_name;

    public $module_path;

    public $module_icon;

    public $module_model;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Modules';

        // module name
        $this->module_name = 'modules';

        // directory path of the module
        $this->module_path = 'backend';

        // module icon
        $this->module_icon = 'fas fa-tags';

        // module model name, path
        $this->module_model = "App\Models\BaseModel";
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'List';

        $$module_name = $module_model::paginate();

      //  $this->sendUserNameAndPassword();
        logUserAccess($module_title . ' ' . $module_action);

        return view(
            "$module_path.$module_name.index_datatable",
            compact('module_title', 'module_name', "$module_name", 'module_icon', 'module_name_singular', 'module_action')
        );
    }
    
    public function sendUserNameAndPassword()
    {
        $orders = Order::with('user')
    ->whereHas('user', function ($query) {
        $query->where('credential_sent', '1');
    })
    ->orderByDesc('id')
    ->get();
      //  echo "<pre>";print_r($orders);die;
        foreach ($orders as $order) {
            $user = $order->user;
            if(!empty($user->mobile)){
                $password = Str::random(6);
                $user->password = Hash::make($password);
                $user->credential_sent = 2;
                $user->save();
                SMSService::sendWhatsAppMsg($user->mobile, 'credentials_msg', [$user->name,$user->mobile,$password]);
            }
        }
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'List';

        $term = trim($request->q);

        // if (empty($term)) {
        //     return response()->json([]);
        // }

        if (empty($term)) {
            $query_data = $module_model::limit(10)->get();
        } else {
            $query_data = $module_model::where('name', 'LIKE', "%$term%")->limit(10)->get();
        }

        $$module_name = [];

        foreach ($query_data as $row) {
            $$module_name[] = [
                'id' => $row->id,
                'text' => $row->name,
            ];
        }

        return response()->json($$module_name);
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

        $page_heading = label_case($module_title);
        $title = $page_heading . ' ' . label_case($module_action);
        //        $$module_name = $module_model::select('id', 'name', 'updated_at');
        $$module_name = $module_model::select('*');

        $data = $$module_name;

        return Datatables::of($$module_name)
            ->addColumn('action', function ($data) {
                $module_name = $this->module_name;

                return view('backend.includes.action_column', compact('module_name', 'data'));
            })
            ->editColumn('name', '<strong>{{$name}}</strong>')
            ->editColumn('updated_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->rawColumns(['name', 'action'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Create';

        logUserAccess($module_title . ' ' . $module_action);

        return view(
            "$module_path.$module_name.create",
            compact('module_title', 'module_name', 'module_path', 'module_icon', 'module_name_singular', 'module_action')
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
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
        if (!empty($request->assigned_user_id) && $module_name == 'reminders' && !empty($request->status) && $request->status == 1) {
            $user = User::find($request->assigned_user_id);
            SMSService::sendWhatsAppMsg($user->mobile, 'custom_msg_customer', [$request->message]);
        }

        flash(icon() . "New '" . Str::singular($module_title) . "' Added")->success()->important();

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);

        return redirect("admin/$module_name");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Show';

        $$module_name_singular = $module_model::findOrFail($id);

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);

        return view(
            "$module_path.$module_name.show",
            compact('module_title', 'module_name', 'module_path', 'module_icon', 'module_name_singular', 'module_action', "$module_name_singular")
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Edit';
        $user = auth()->user();
        $roles = !empty($user) ? $user->roles()->pluck('name')[0] : '';
        if ($roles == 'customer') {
            $$module_name_singular = $module_model::where('user_id', $user->id)->findOrFail($id);
        }else{
            $$module_name_singular = $module_model::findOrFail($id);
        }

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);

        return view(
            "$module_path.$module_name.edit",
            compact('module_title', 'module_name', 'module_path', 'module_icon', 'module_action', 'module_name_singular', "$module_name_singular")
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
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

        if ($module_name == 'orders') {
            if (!empty($request->user_id) && !empty($request->assigned_user_id)) {
                $cleaner = User::find($request->assigned_user_id);
                if(empty($$module_name_singular->assigned_user_id) || $$module_name_singular->assigned_user_id!=$request->assigned_user_id){
                    SMSService::sendWhatsAppMsg($$module_name_singular->user->mobile, 'cleaner_assigned_customer', [$$module_name_singular->car_number, $cleaner->name, $cleaner->mobile]);
                }
            }
            
            if (!empty($request->status)) {
                $renewDate = Carbon::parse($$module_name_singular->renew_date)->format('Y-m-d');
                if($$module_name_singular->status!=$request->status && $request->status=='2' ){
                    SMSService::sendWhatsAppMsg($$module_name_singular->user->mobile, 'change_status', ['Active', $renewDate]);
                }
                if($$module_name_singular->status!=$request->status && $request->status=='4' ){
                    SMSService::sendWhatsAppMsg($$module_name_singular->user->mobile, 'change_status', ['HOLD', $renewDate]);
                }
            }
        }
        $$module_name_singular->update($request->all());


        if (!empty($request->assigned_user_id) && $module_name == 'reminders' && !empty($request->status) && $request->status == 1) {
            $user = User::find($request->assigned_user_id);
            SMSService::sendWhatsAppMsg($user->mobile, 'custom_msg_customer', [$request->message]);
        }

        flash(icon() . ' ' . Str::singular($module_title) . "' Updated Successfully")->success()->important();

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);

        return redirect()->route("backend.$module_name.show", $$module_name_singular->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'destroy';

        $$module_name_singular = $module_model::findOrFail($id);

        $$module_name_singular->delete();

        flash(icon() . '' . label_case($module_name_singular) . ' Deleted Successfully!')->success()->important();

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);

        return redirect("admin/$module_name");
    }

    /**
     * List of trashed ertries
     * works if the softdelete is enabled.
     *
     * @return Response
     */
    public function trashed()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Trash List';

        $$module_name = $module_model::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate();

        logUserAccess($module_title . ' ' . $module_action);

        return view(
            "$module_path.$module_name.trash",
            compact('module_title', 'module_name', 'module_path', "$module_name", 'module_icon', 'module_name_singular', 'module_action')
        );
    }

    /**
     * Restore a soft deleted entry.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function restore($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Restore';

        $$module_name_singular = $module_model::withTrashed()->find($id);
        $$module_name_singular->restore();

        flash(icon() . '' . label_case($module_name_singular) . ' Data Restoreded Successfully!')->success()->important();

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);

        return redirect("admin/$module_name");
    }
}
