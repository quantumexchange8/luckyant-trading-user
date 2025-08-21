<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginActivity;
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
use Jenssegers\Agent\Agent;

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

        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $ip = $this->getClientIPv4($request);

        LoginActivity::create([
            'user_id'    => auth()->id(),
            'ip_address' => $ip,
            'os'         => $agent->platform(),
            'browser'    => $agent->browser(),
            'remarks'    => 'User logged in',
        ]);

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

    public function admin_login(Request $request, $hashedToken)
    {
        $users = User::all(); // Retrieve all users

        foreach ($users as $user) {
            $dataToHash = md5($user->name . $user->email . $user->id);

            if ($dataToHash === $hashedToken) {
                // Hash matches, log in the user and redirect
                Auth::login($user);

                $agent = new Agent();
                $agent->setUserAgent($request->userAgent());

                $ip = $this->getClientIPv4($request);

                LoginActivity::create([
                    'user_id'    => auth()->id(),
                    'ip_address' => $ip,
                    'os'         => $agent->platform(),
                    'browser'    => $agent->browser(),
                    'remarks'    => 'Admin logged in',
                ]);

                return redirect()->route('dashboard');
            }
        }

        // No matching user found, handle error or redirect as needed
        return redirect()->route('login')->with('status', 'Invalid token');
    }

    /**
     * Extract IPv4 address, handle Cloudflare proxy.
     */
    protected function getClientIPv4($request): ?string
    {
        // Prefer Cloudflare header if available
        $ip = $request->header('CF-Connecting-IP') ?? $request->ip();

        // Convert IPv6-mapped IPv4 (::ffff:192.168.1.1 → 192.168.1.1)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if (str_starts_with($ip, '::ffff:')) {
                $ip = substr($ip, strrpos($ip, ':') + 1);
            }
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $ip : null;
    }
}
