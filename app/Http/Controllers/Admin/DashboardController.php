<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * P3-T8: owner dashboard. Counts, upcoming events, unpaid invoices and
 * invitations that expire soon.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->startOfDay();
        $in30 = now()->addDays(30)->endOfDay();
        $in7 = now()->addDays(7)->endOfDay();

        $counts = Invitation::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $upcoming = Invitation::query()
            ->whereNotNull('event_date')
            ->whereBetween('event_date', [$today->toDateString(), $in30->toDateString()])
            ->orderBy('event_date')
            ->limit(8)
            ->get(['id', 'title', 'slug', 'event_date', 'status'])
            ->map(fn (Invitation $invitation) => [
                'id' => $invitation->id,
                'title' => $invitation->title,
                'slug' => $invitation->slug,
                'event_date' => $invitation->event_date?->toDateString(),
                'status' => $invitation->status,
            ]);

        $unpaid = Invitation::query()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('id')
            ->limit(8)
            ->get(['id', 'title', 'slug', 'payment_status', 'price_agreed', 'amount_paid'])
            ->map(fn (Invitation $invitation) => [
                'id' => $invitation->id,
                'title' => $invitation->title,
                'slug' => $invitation->slug,
                'payment_status' => $invitation->payment_status,
                'price_agreed' => $invitation->price_agreed,
                'amount_paid' => $invitation->amount_paid,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'counts' => [
                'active' => (int) ($counts['active'] ?? 0),
                'draft' => (int) ($counts['draft'] ?? 0),
                'expired' => (int) ($counts['expired'] ?? 0),
                'archived' => (int) ($counts['archived'] ?? 0),
                'total' => (int) $counts->sum(),
            ],
            'upcoming' => $upcoming,
            'unpaid' => $unpaid,
            'unpaidCount' => Invitation::whereIn('payment_status', ['unpaid', 'partial'])->count(),
            'expiringSoon' => Invitation::query()
                ->whereNotNull('active_until')
                ->whereBetween('active_until', [now(), $in7])
                ->count(),
            'selectedInvitationId' => $request->session()->get(\App\Http\Middleware\ResolveAdminTenant::SESSION_KEY),
        ]);
    }
}
