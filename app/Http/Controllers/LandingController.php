<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Package;
use App\Support\Settings;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

/**
 * P3-T6: marketing landing page.
 *
 * Prices come from the database and may be null; the UI shows "Hubungi kami"
 * for those. The WhatsApp CTA is only rendered when the owner configured a
 * number - never invented.
 */
class LandingController extends Controller
{
    public function __invoke(Request $request)
    {
        // Legacy printed QR / WhatsApp links point at `/?guest=CODE`. Resolve
        // them to the personalised slug URL instead of showing the landing page.
        $code = $request->query('guest');

        if (is_string($code) && $code !== '') {
            $url = $this->legacyGuestUrl($code);

            // Contract (AGENTS.md 5.2 / matrix T11): an unknown guest code is
            // a 404, never a silent fallback to the landing page.
            abort_if($url === null, 404);

            return redirect()->to($url);
        }

        $business = Settings::get('business_name');
        $whatsapp = Settings::get('whatsapp_number');

        $packages = Package::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'price' => $package->price,
                'features' => collect($package->featureFlags())
                    ->filter()
                    ->keys()
                    ->map(fn (string $key) => config("features.{$key}.label", $key))
                    ->values()
                    ->all(),
            ]);

        $demo = Invitation::query()
            ->where('is_demo', true)
            ->where('status', 'active')
            ->orderBy('id')
            ->first(['id', 'slug', 'title']);

        $seo = [
            'title' => ($business ? $business.' - ' : '').'Undangan Pernikahan Digital',
            'description' => 'Undangan pernikahan digital dengan QR tamu, buku tamu, photobooth, dan buku kenangan cetak.',
            'image' => Settings::get('platform_logo'),
            'url' => url('/'),
        ];

        View::share('seo', $seo);

        return Inertia::render('Landing', [
            'platform' => [
                'business_name' => $business,
                'instagram_url' => Settings::get('instagram_url'),
                'footer_text' => Settings::get('footer_text'),
                'logo_url' => Settings::get('platform_logo'),
            ],
            'whatsappUrl' => $this->whatsappUrl($whatsapp),
            'packages' => $packages,
            'demoUrl' => $demo !== null ? url('/'.$demo->slug) : null,
        ]);
    }

    /**
     * wa.me link with a prefilled message. Null when no number is configured.
     */
    private function whatsappUrl(?string $number): ?string
    {
        if (! is_string($number)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        $message = 'Halo, saya ingin bertanya tentang undangan pernikahan digital.';

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    /**
     * Resolve a legacy `?guest=CODE` link to its personalised slug URL.
     * Returns null when the code is unknown (the caller then shows the landing).
     */
    private function legacyGuestUrl(string $code): ?string
    {
        // The tenant is unknown until the guest is found, so this single
        // lookup must bypass the scope. The code comes only from the query
        // string and is used solely to discover the tenant (K5 compatibility).
        $guest = app(TenantContext::class)->withoutScope(
            fn () => \App\Models\Guest::where('guest_code', $code)->first()
        );

        if ($guest === null || $guest->invitation_id === null) {
            return null;
        }

        $invitation = Invitation::find($guest->invitation_id);

        if ($invitation === null) {
            return null;
        }

        return url('/'.$invitation->slug).'?guest='.urlencode($code);
    }

    /**
     * Legal pages. TODO(owner): replace with reviewed text before publishing.
     */
    public function terms()
    {
        return $this->legal('Terms', 'Syarat & Ketentuan');
    }

    public function privacy()
    {
        return $this->legal('Privacy', 'Kebijakan Privasi');
    }

    private function legal(string $page, string $title)
    {
        View::share('seo', [
            'title' => $title,
            'description' => $title,
            'image' => Settings::get('platform_logo'),
            'url' => url()->current(),
        ]);

        return Inertia::render('Legal/'.$page, [
            'title' => $title,
            'businessName' => Settings::get('business_name'),
        ]);
    }
}
