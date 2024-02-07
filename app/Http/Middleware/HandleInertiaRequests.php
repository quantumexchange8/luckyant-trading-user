<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'auth.user.profile_photo' => fn() => $request->user() ? $request->user()->getFirstMediaUrl('profile_photo') : null,
            'toast' => session('toast'),
            'title' => session('title'),
            'success' => session('success'),
            'warning' => session('warning'),
            'auth.user.wallets' => fn() => $request->user() ? $request->user()->wallets : null,
        ];
    }
}
