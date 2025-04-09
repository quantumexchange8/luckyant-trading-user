<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user && $user->sync_mall) {
            return back()->with('toast', [
                'title' => trans("public.warning"),
                'message' => trans('public.invalid_action'),
                'type' => 'warning',
            ]);
        }

        $request->authenticate();

        $request->session()->regenerate();
        Session::put('first_time_logged_in', 1);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function authorize_user_account(Request $request)
    {
        return Inertia::render('Auth/AuthorizeAccount', [
            'username' => $request->username,
        ]);
    }

    public function authorize_account(Request $request)
    {
        Validator::make($request->all(), [
            'username' => ['required', 'string', 'regex:/^[\p{L}\p{N}\p{M}. @]+$/u'],
            'password' => ['required', 'string'],
        ])->setAttributeNames([
            'username' => trans('public.username'),
            'password' => trans('public.password'),
        ])->validate();

        if (User::where('username', $request->username)->exists()) {
            $user = User::firstWhere('username', $request->username);
            // Check password
            if (!Hash::check($request->get('password'), $user->password)) {
                throw ValidationException::withMessages([
                    'password' => trans('public.invalid_password'),
                ]);
            }

            $params = [
                'token' => md5($user->name . $user->email . $user->username),
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'dial_code' => $user->dial_code,
                'phone_number' => $user->phone,
                'dob' => $user->dob,
                'country_id' => $user->country,
            ];

            $redirectUrl = "http://luckyantmall-user.test/authorize_login?" . http_build_query($params);

            return Inertia::location($redirectUrl);
        } else {
            throw ValidationException::withMessages([
                'username' => trans('public.account_not_found'),
            ]);
        }
    }
}
