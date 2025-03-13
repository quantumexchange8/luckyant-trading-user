<?php

namespace App\Http\Middleware;

use App\Services\SidebarService;
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
        $sidebarService = new SidebarService();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'user.rank' => $request->user() ? $request->user()->rank : null,
                'user.payment_accounts' => $request->user() ? $request->user()->payment_accounts : null,
            ],
            'auth.user.profile_photo' => fn() => $request->user() ? $request->user()->getFirstMediaUrl('profile_photo') : null,
            'toast' => session('toast'),
            'title' => session('title'),
            'success' => session('success'),
            'warning' => session('warning'),
            'auth.user.wallets' => fn() => $request->user() ? $request->user()->wallets : null,
            'locale' => session('locale') ? session('locale') : app()->getLocale(),
            'getSidebarContentVisibility' => $request->user() ? $sidebarService->getSidebarContentVisibility() : null,
            'canAccessApplication' => $request->user() ? $sidebarService->canAccessApplication() : null,
            'firstTimeLogin' => session('first_time_logged_in', 0),
        ];
    }
}
