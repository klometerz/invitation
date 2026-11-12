<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $tenantId = app(TenantContext::class)->id();
        $tenant = $tenantId !== null ? Invitation::find($tenantId) : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_admin' => $user->isAdmin(),
                ] : null,
            ],
            // Current tenant (null on public pages that resolve no tenant).
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'title' => $tenant->title,
                'slug' => $tenant->slug,
                'features' => $tenant->featureFlags(),
            ] : null,
            // Event switcher options: owner only.
            'invitations' => $user?->isAdmin()
                ? Invitation::query()
                    ->orderBy('id')
                    ->get(['id', 'title', 'slug', 'status'])
                    ->map(fn (Invitation $invitation) => [
                        'id' => $invitation->id,
                        'title' => $invitation->title,
                        'slug' => $invitation->slug,
                        'status' => $invitation->status,
                    ])
                    ->all()
                : [],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // Platform branding comes from settings (P3-T5 builds the GUI);
            // until it is filled in the UI renders a text mark, never another
            // client's logo.
            'platform' => [
                'name' => \App\Support\Settings::get('business_name'),
                'logo_url' => \App\Support\Settings::get('platform_logo'),
            ],
        ];
    }
}
