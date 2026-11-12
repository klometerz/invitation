<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Invitation;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        // Scoped automatically by TenantScope.
        $query = Guest::latest();

        if ($request->filled('filter')) {
            if ($request->filter === 'arrived') {
                $query->where('checkin_status', 'arrived');
            } elseif ($request->filter === 'not_arrived') {
                $query->where('checkin_status', 'not_arrived');
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('category', 'like', '%'.$request->search.'%');
            });
        }

        $paginated = $query->paginate(25)->withQueryString();

        $invitation = $this->currentInvitation();
        $baseUrl = url('/'.$invitation->slug);

        $paginated->getCollection()->transform(function ($guest) use ($baseUrl) {
            $url = $baseUrl.'?guest='.$guest->guest_code;

            return [
                'id' => $guest->id,
                'name' => $guest->name,
                'city' => $guest->city,
                'category' => $guest->category,
                'quota' => $guest->quota,
                'code' => $guest->guest_code,
                'link' => $url,
                'qrcode' => QrCode::size(100)->generate($url)->toHtml(),
                'checkin_status' => $guest->checkin_status,
                'checked_in_at' => $guest->checked_in_at?->format('d M, H:i'),
                'is_rsvp' => $guest->is_rsvp,
                'attendance' => $guest->attendance,
                'message' => $guest->message,
            ];
        });

        $summary = [
            'total' => Guest::count(),
            'arrived' => Guest::where('checkin_status', 'arrived')->count(),
            'not_arrived' => Guest::where('checkin_status', 'not_arrived')->count(),
            'rsvp_confirmed' => Guest::where('is_rsvp', 1)->where('attendance', 'confirmed')->count(),
            'rsvp_declined' => Guest::where('is_rsvp', 1)->where('attendance', 'declined')->count(),
            'rsvp_pending' => Guest::where('is_rsvp', 0)->count(),
        ];

        // Ambil kategori unik yang udah ada di database, buat filter dropdown
        $categories = Guest::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return Inertia::render('Admin/GuestIndex', [
            'guests' => $paginated,
            'summary' => $summary,
            'categories' => $categories,
            'invitation' => [
                'id' => $invitation->id,
                'slug' => $invitation->slug,
                'title' => $invitation->title,
            ],
            // P2-T8: the invite message is a template with placeholders,
            // editable per invitation.
            'inviteTemplate' => \App\Support\InvitationPresenter::for($invitation)->inviteMessageTemplate(),
            'coupleNames' => \App\Support\InvitationPresenter::for($invitation)->coupleNames(),
            'filters' => [
                'filter' => $request->filter,
                'search' => $request->search,
                'category' => $request->category,
            ],
        ]);
    }

    /**
     * Manual create tamu baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'quota' => 'required|integer|min:1|max:20',
        ]);

        // P3-T3: package guest limit.
        $limit = $this->currentInvitation()->limit('max_guests');

        if ($limit !== null && Guest::count() >= $limit) {
            return back()->withErrors([
                'name' => "Batas paket tercapai ({$limit} tamu). Hapus tamu lain atau tingkatkan paket.",
            ]);
        }

        // invitation_id is filled from TenantContext by the trait.
        Guest::create($validated);

        return back()->with('success', 'Tamu baru berhasil ditambahkan!');
    }

    /**
     * Edit data tamu (nama, kota, kategori, quota)
     * guest_code TIDAK diubah biar QR yang udah dicetak tetap valid
     */
    public function update(Request $request, $id)
    {
        // Scoped: an id from another tenant 404s here (matrix row T4).
        $guest = Guest::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'quota' => 'required|integer|min:1|max:20',
        ]);

        $guest->update($validated);

        return back()->with('success', 'Data tamu berhasil diupdate!');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $data = Excel::toArray([], $file);
        $rows = $data[0] ?? [];

        $invitationId = app(TenantContext::class)->id() ?? $this->currentInvitation()->id;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        // P3-T3: stop importing once the package limit is reached, and report
        // exactly how many rows were skipped.
        $limit = $this->currentInvitation()->limit('max_guests');
        $current = Guest::count();

        foreach (array_slice($rows, 1) as $row) {
            if (empty($row[0])) {
                continue;
            }

            $exists = Guest::where('invitation_id', $invitationId)
                ->where('name', $row[0])
                ->where('city', $row[1] ?? '-')
                ->exists();

            if (! $exists) {
                if ($limit !== null && $current >= $limit) {
                    $skipped++;

                    continue;
                }

                $current++;
            }

            // K3: match inside the tenant on (invitation_id, name, city) so a
            // same-named guest in another wedding is never touched.
            $guest = Guest::updateOrCreate(
                [
                    'invitation_id' => $invitationId,
                    'name' => $row[0],
                    'city' => $row[1] ?? '-',
                ],
                [
                    'category' => $row[2] ?? null,
                    'quota' => $row[3] ?? 1,
                ]
            );

            $guest->wasRecentlyCreated ? $created++ : $updated++;
        }

        $message = "Import selesai: {$created} tamu baru, {$updated} tamu diperbarui.";

        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati karena batas paket ({$limit} tamu).";
        }

        return back()->with('success', $message);
    }

    public function show($code)
    {
        // Scoped by the guest-code middleware; another tenant's code 404s.
        $guest = Guest::where('guest_code', $code)->firstOrFail();

        return response()->json([
            'name' => $guest->name,
            'code' => $guest->guest_code,
            'quota' => $guest->quota,
            'city' => $guest->city,
        ]);
    }

    public function printLabels(Request $request)
    {
        $query = Guest::orderBy('name', 'asc');

        // Support single ID (?id=5) ATAU multiple IDs (?ids=5,12,20)
        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('id', $ids);
        } elseif ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        $invitation = $this->currentInvitation();
        $baseUrl = url('/'.$invitation->slug);

        $guests = $query->get()->map(function ($guest) use ($baseUrl) {
            return [
                'name' => $guest->name,
                'qrcode' => QrCode::size(80)->margin(0)->generate($baseUrl.'?guest='.$guest->guest_code)->toHtml(),
            ];
        });

        return Inertia::render('Admin/GuestPrintLabels', [
            'guests' => $guests,
            'eventTitle' => $invitation->title,
            'isSingle' => $request->filled('id') || ($request->filled('ids') && count(explode(',', $request->ids)) === 1),
        ]);
    }

    public function updateRsvp(Request $request, $code)
    {
        $guest = Guest::where('guest_code', $code)->firstOrFail();

        if ($guest->is_rsvp) {
            return response()->json([
                'message' => 'Anda sudah melakukan konfirmasi kehadiran sebelumnya.',
                'already_submitted' => true,
                'attendance' => $guest->attendance,
            ], 409);
        }

        $validated = $request->validate([
            'status' => 'required|in:confirmed,declined',
            'message' => 'nullable|string|max:1000',
        ]);

        $guest->update([
            'is_rsvp' => 1,
            'attendance' => $validated['status'],
            'message' => $validated['message'] ?? null,
        ]);

        return response()->json([
            'message' => 'Terima kasih atas konfirmasi kehadiran Anda!',
            'attendance' => $guest->attendance,
        ]);
    }

    public function destroy($id)
    {
        Guest::findOrFail($id)->delete();

        return back();
    }

    /**
     * Replaces the old unscoped truncate (K2). Deletes guests of the CURRENT
     * invitation only, and requires the operator to type the invitation slug.
     */
    public function resetGuests(Request $request)
    {
        $invitation = $this->currentInvitation();

        $validated = $request->validate([
            'confirm_slug' => 'required|string',
        ]);

        if ($validated['confirm_slug'] !== $invitation->slug) {
            return back()->withErrors([
                'confirm_slug' => 'Slug undangan tidak cocok. Penghapusan dibatalkan.',
            ]);
        }

        $deleted = Guest::query()->delete();

        return back()->with('success', "{$deleted} data tamu undangan ini telah dihapus.");
    }

    public function export()
    {
        $invitation = $this->currentInvitation();

        return Excel::download(
            new \App\Exports\GuestsExport,
            'daftar-tamu-'.$invitation->slug.'-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    private function currentInvitation(): Invitation
    {
        return Invitation::findOrFail(app(TenantContext::class)->require());
    }
}
