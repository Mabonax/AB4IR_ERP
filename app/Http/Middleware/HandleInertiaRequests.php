<?php

namespace App\Http\Middleware;

use App\Domains\Organization\Services\OrganizationProfileService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'name' => config('app.name'),

            'auth' => [
                'user' => fn () => $request->user()
                    ? array_merge(
                        $request->user()->toArray(),
                        [
                            'roles' => $request->user()->getRoleNames()->values(),
                            'permissions' => $request->user()->getAllPermissions()->pluck('name')->values(),
                            'unread_notifications_count' => $request->user()->unreadNotifications()->count(),
                        ]
                    )
                    : null,
            ],

            'notifications' => [
                'unread_count' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            ],

            'organization' => fn () => app(OrganizationProfileService::class)->mapProfile(
                app(OrganizationProfileService::class)->getProfile()
            ),

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

            /*
            |--------------------------------------------------------------------------
            | Flash Messages (THIS WAS MISSING)
            |--------------------------------------------------------------------------
            */
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'message' => fn () => $request->session()->get('message'),
                'status' => fn () => $request->session()->get('status'),
                'import_errors' => fn () => $request->session()->get('import_errors', []),
            ],
        ]);
    }
}
