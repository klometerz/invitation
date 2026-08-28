<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Inertia\Inertia;

class GuestController extends Controller {

    public function index(Request $request) {
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
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        $paginated = $query->paginate(25)->withQueryString();

        $paginated->getCollection()->transform(function($guest) {
            $url = url('/?guest=' . $guest->guest_code);
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
    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'quota' => 'required|integer|min:1|max:20',
        ]);

        Guest::create($validated);

        return back()->with('success', 'Tamu baru berhasil ditambahkan!');
    }

    /**
     * Edit data tamu (nama, kota, kategori, quota)
     * guest_code TIDAK diubah biar QR yang udah dicetak tetap valid
     */
    public function update(Request $request, $id) {
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

    public function import(Request $request) {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');
        $data = Excel::toArray([], $file);
        $rows = $data[0];

        foreach (array_slice($rows, 1) as $row) {
            if (!empty($row[0])) {
                Guest::updateOrCreate(
                    ['name' => $row[0]], 
                    [
                        'city' => $row[1] ?? '-',
                        'category' => $row[2] ?? null,
                        'quota' => $row[3] ?? 1,
                    ]
                );
            }
        }

        return back()->with('success', 'Data tamu berhasil di-import!');
    }

    public function show($code) {
        $guest = Guest::where('guest_code', $code)->firstOrFail();

        return response()->json([
            'name' => $guest->name,
            'code' => $guest->guest_code,
            'quota' => $guest->quota,
            'city' => $guest->city
        ]);
    }

    // public function printLabels() {
    //     $guests = Guest::orderBy('name', 'asc')->get()->map(function($guest) {
    //         $url = url('/?guest=' . $guest->guest_code);
    //         return [
    //             'name' => $guest->name,
    //             'qrcode' => QrCode::size(80)->margin(0)->generate($url)->toHtml()
    //         ];
    //     });

    //     return Inertia::render('Admin/GuestPrintLabels', [
    //         'guests' => $guests
    //     ]);
    // }

//     public function printLabels(Request $request) {
//         $invitation = \App\Models\Invitation::first();
//     $query = Guest::orderBy('name', 'asc');

//     // Kalau ada parameter ?id=xxx, filter cuma 1 tamu itu aja
//     if ($request->filled('id')) {
//         $query->where('id', $request->id);
//     }

//     $guests = $query->get()->map(function($guest) {
//         $url = url('/?guest=' . $guest->guest_code);
//         return [
//             'name' => $guest->name,
//             'qrcode' => QrCode::size(80)->margin(0)->generate($url)->toHtml()
//         ];
//     });

//     return Inertia::render('Admin/GuestPrintLabels', [
//         'guests' => $guests,
//          'eventTitle' => $invitation?->title ?? 'The Wedding',
//         'isSingle' => $request->filled('id'),
//     ]);
// }

public function printLabels(Request $request) {
    $query = Guest::orderBy('name', 'asc');

    // Support single ID (?id=5) ATAU multiple IDs (?ids=5,12,20)
    if ($request->filled('ids')) {
        $ids = explode(',', $request->ids);
        $query->whereIn('id', $ids);
    } elseif ($request->filled('id')) {
        $query->where('id', $request->id);
    }

    $guests = $query->get()->map(function($guest) {
        $url = url('/?guest=' . $guest->guest_code);
        return [
            'name' => $guest->name,
            'qrcode' => QrCode::size(80)->margin(0)->generate($url)->toHtml()
        ];
    });

    return Inertia::render('Admin/GuestPrintLabels', [
        'guests' => $guests,
        'isSingle' => $request->filled('id') || ($request->filled('ids') && count(explode(',', $request->ids)) === 1),
    ]);
}
    public function updateRsvp(Request $request, $code) {
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

    public function destroy($id) {
        Guest::findOrFail($id)->delete();
        return back();
    }

    public function truncate() 
    {
        \App\Models\Guest::truncate();
        return back()->with('success', 'Semua data tamu telah dimusnahkan, Bre!');
    }

    public function export() 
    {
        return Excel::download(new \App\Exports\GuestsExport, 'daftar-tamu-' . now()->format('Y-m-d') . '.xlsx');
    }
}