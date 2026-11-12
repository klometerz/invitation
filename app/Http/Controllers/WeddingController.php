<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Public invitation page: `/{slug}` and `/{slug}?guest=CODE`.
 *
 * The tenant is resolved by the `tenant.public` middleware, which stores the
 * invitation on the request and sets TenantContext; every query below is
 * therefore already restricted to this invitation.
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

        $guestCode = $request->query('guest');
        $guest = null;

        if (is_string($guestCode) && $guestCode !== '') {
            // Scoped by TenantContext: a code belonging to another invitation
            // simply does not exist here (matrix row T5).
            $guest = Guest::where('guest_code', $guestCode)->first();
        }

        if ($guest !== null) {
            $qrUrl = url('/'.$invitation->slug.'?guest='.$guest->guest_code);
            $guest->qrcode = QrCode::size(200)->margin(1)->generate($qrUrl)->toHtml();
        }

        $galleries = Gallery::orderBy('order')->get();

        View::share('seo', [
            'title' => $invitation->title ?: 'Undangan Pernikahan',
            'description' => 'Anda diundang untuk hadir di acara pernikahan kami.',
            'image' => $galleries->isNotEmpty() ? url($galleries->first()->path) : url('/mk.png'),
            'url' => url()->current(),
        ]);

        return Inertia::render('Wedding', [
            'invitation' => $invitation,
            'content' => $invitation->content,
            'guestData' => $guest,
            'galleries' => $galleries,
        ]);
    }
}
