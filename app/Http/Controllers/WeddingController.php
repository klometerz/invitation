<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\Guest;
use App\Models\Invitation;
use App\Support\InvitationPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Public invitation page: `/{slug}` and `/{slug}?guest=CODE`.
 *
 * P2-T5 (K6): everything sent to the browser is a whitelisted array. No raw
 * Eloquent model leaves this controller.
 */
class WeddingController extends Controller
{
    public function index(Request $request)
    {
        /** @var Invitation $invitation */
        $invitation = $request->attributes->get('invitation');

        if (! $invitation instanceof Invitation) {
            abort(404);
        }

        $presenter = InvitationPresenter::for($invitation);
        $meta = $presenter->toArray();

        $guestCode = $request->query('guest');
        $guest = null;

        if (is_string($guestCode) && $guestCode !== '') {
            // Scoped by TenantContext: a code belonging to another invitation
            // simply does not exist here (matrix row T5).
            $guest = Guest::where('guest_code', $guestCode)->first();
        }

        View::share('seo', $meta['seo']);

        return Inertia::render('Invitation/Show', [
            'invitation' => [
                'id' => $invitation->id,
                'title' => $invitation->title,
                'slug' => $invitation->slug,
            ],
            'theme' => [
                'key' => $invitation->theme?->key ?? 'heritage-gold',
            ],
            'content' => $invitation->content,
            'sections' => $invitation->resolvedSections(),
            'features' => $invitation->featureFlags(),
            'guestData' => $guest !== null ? $this->guestData($guest, $invitation) : null,
            'galleries' => Gallery::orderBy('order')
                ->get(['id', 'path'])
                ->map(fn (Gallery $gallery) => [
                    'id' => $gallery->id,
                    'path' => $gallery->path,
                ])
                ->all(),
            'meta' => $meta,
        ]);
    }

    /**
     * Whitelisted guest payload (theme contract, AGENTS.md 5.8).
     *
     * @return array<string, mixed>
     */
    private function guestData(Guest $guest, Invitation $invitation): array
    {
        $qrUrl = url('/'.$invitation->slug.'?guest='.$guest->guest_code);

        return [
            'name' => $guest->name,
            'guest_code' => $guest->guest_code,
            'quota' => (int) $guest->quota,
            'is_rsvp' => (bool) $guest->is_rsvp,
            'attendance' => $guest->attendance,
            'checkin_status' => $guest->checkin_status,
            'qrcode' => QrCode::size(200)->margin(1)->generate($qrUrl)->toHtml(),
        ];
    }
}
