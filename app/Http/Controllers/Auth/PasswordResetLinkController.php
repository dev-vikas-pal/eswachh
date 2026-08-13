<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Services\SMSService;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required'],
        ]);

        $temp_password = Str::random(6);
        $user  = User::where('mobile',$request->email)->first();
        if(!empty($user->id)){
            $user->password = Hash::make($temp_password);
            $user->save();
            SMSService::sendWhatsAppMsg($user->mobile, 'credentials_msg', [$user->name,$user->mobile,$temp_password]);
            return redirect()->route('login')->with('status', 'New credentials have been sent to your WhatsApp.');
        }
        return back()->withErrors([
            'mobile' => 'The provided mobile number does not match our records.',
        ]);
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
