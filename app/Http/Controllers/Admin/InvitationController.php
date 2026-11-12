<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveAdminTenant;
use App\Models\Invitation;
use App\Models\Package;
use App\Models\Theme;
use App\Support\SlugRules;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Tenant-management screens: the list of weddings (events).
 *
 * Fixes K1's admin half: nothing here uses Invitation::first() or creates a
 * default row behind the operator's back.
 */
class InvitationController extends Controller
{
    public function index(Request $request)
    {
        $query = Invitation::query()->with(['theme', 'package'])->orderBy('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('client_name', 'like', '%'.$search.'%');
            });
        }

        $invitations = $query->paginate(20)->withQueryString()->through(function (Invitation $invitation) {
            return [
                'id' => $invitation->id,
                'title' => $invitation->title,
                'slug' => $invitation->slug,
                'status' => $invitation->status,
                'theme' => $invitation->theme?->name,
                'package' => $invitation->package?->name,
                'event_date' => $invitation->event_date?->toDateString(),
                'payment_status' => $invitation->payment_status,
                'active_until' => $invitation->active_until?->toDateTimeString(),
                'public_url' => url('/'.$invitation->slug),
            ];
        });

        return Inertia::render('Admin/InvitationIndex', [
            'invitations' => $invitations,
            'selectedInvitationId' => $request->session()->get(ResolveAdminTenant::SESSION_KEY),
            'filters' => ['search' => $request->search],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/InvitationForm', [
            'invitation' => null,
            'themes' => $this->themeOptions(),
            'packages' => $this->packageOptions(),
            'reservedSlugs' => SlugRules::RESERVED,
            'sectionOptions' => $this->sectionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $validated['user_id'] = $request->user()->id;
        $validated['content'] = $this->defaultContent($validated['title']);
        $validated['is_active'] = $validated['status'] === 'active';
        $validated['sections'] = $this->normalizeSections($validated['sections'] ?? []);

        $invitation = Invitation::create($validated);

        return redirect()
            ->route('admin.invitations.index')
            ->with('success', 'Undangan "'.$invitation->title.'" berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $invitation = Invitation::findOrFail($id);

        return Inertia::render('Admin/InvitationForm', [
            'invitation' => [
                'id' => $invitation->id,
                'title' => $invitation->title,
                'slug' => $invitation->slug,
                'theme_id' => $invitation->theme_id,
                'package_id' => $invitation->package_id,
                'status' => $invitation->status,
                'event_date' => $invitation->event_date?->toDateString(),
                'active_from' => $invitation->active_from?->format('Y-m-d\TH:i'),
                'active_until' => $invitation->active_until?->format('Y-m-d\TH:i'),
                'client_name' => $invitation->client_name,
                'client_phone' => $invitation->client_phone,
                'client_email' => $invitation->client_email,
                'price_agreed' => $invitation->price_agreed,
                'payment_status' => $invitation->payment_status,
                'amount_paid' => $invitation->amount_paid,
                'paid_at' => $invitation->paid_at?->format('Y-m-d\TH:i'),
                'internal_notes' => $invitation->internal_notes,
                'is_demo' => $invitation->is_demo,
                'sections' => $invitation->resolvedSections(),
            ],
            'themes' => $this->themeOptions(),
            'packages' => $this->packageOptions(),
            'reservedSlugs' => SlugRules::RESERVED,
            'sectionOptions' => $this->sectionOptions(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $invitation = Invitation::findOrFail($id);
        $validated = $this->validated($request, $invitation->id);

        // P3-T2: guard the lifecycle instead of letting any status jump.
        if (! \App\Support\InvitationLifecycle::canTransition($invitation->status, $validated['status'])) {
            return back()->withErrors([
                'status' => "Status tidak bisa diubah dari \"{$invitation->status}\" ke \"{$validated['status']}\".",
            ]);
        }

        $validated['is_active'] = $validated['status'] === 'active';
        $validated['sections'] = $this->normalizeSections($validated['sections'] ?? []);

        $invitation->update($validated);

        return redirect()
            ->route('admin.invitations.index')
            ->with('success', 'Undangan berhasil diperbarui.');
    }

    /**
     * P3-T2: draft|expired -> active, stamping the active window. When the
     * package defines `active_days_after_event` and the event date is known,
     * `active_until` is computed; otherwise it stays whatever the owner set.
     */
    public function activate(int $id)
    {
        $invitation = Invitation::findOrFail($id);

        if (! \App\Support\InvitationLifecycle::canTransition($invitation->status, 'active')
            || $invitation->status === 'active') {
            return back()->with('error', 'Undangan ini belum bisa diaktifkan.');
        }

        $invitation->forceFill([
            'status' => 'active',
            'is_active' => true,
            'active_from' => $invitation->active_from ?? now(),
            'active_until' => $invitation->active_until
                ?? \App\Support\InvitationLifecycle::defaultActiveUntil($invitation),
        ])->save();

        return back()->with('success', 'Undangan "'.$invitation->title.'" diaktifkan.');
    }

    /**
     * Copy content/theme/package/sections into a new draft. Guests, gallery,
     * photobooth photos and payment state are deliberately NOT copied.
     */
    public function duplicate(int $id)
    {
        $source = Invitation::findOrFail($id);

        $copy = $source->replicate();
        $copy->slug = $this->uniqueSlug($source->slug.'-copy');
        $copy->title = $source->title.' (Copy)';
        $copy->status = 'draft';
        $copy->is_active = false;
        $copy->is_demo = false;

        // Payment state must not follow the copy.
        $copy->price_agreed = null;
        $copy->payment_status = 'unpaid';
        $copy->amount_paid = 0;
        $copy->paid_at = null;

        $copy->save();

        return redirect()
            ->route('admin.invitations.index')
            ->with('success', 'Undangan diduplikasi menjadi "'.$copy->title.'".');
    }

    public function archive(int $id)
    {
        $invitation = Invitation::findOrFail($id);
        $invitation->update(['status' => 'archived', 'is_active' => false]);

        return back()->with('success', 'Undangan "'.$invitation->title.'" diarsipkan.');
    }

    /**
     * Event switcher: picks which tenant the admin works on.
     */
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'invitation_id' => ['required', 'integer', 'exists:invitations,id'],
        ]);

        $request->session()->put(
            ResolveAdminTenant::SESSION_KEY,
            (int) $validated['invitation_id']
        );

        return back()->with('success', 'Acara berhasil dipilih.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreInvitationId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => SlugRules::validationRules($ignoreInvitationId),
            'theme_id' => ['required', 'integer', 'exists:themes,id'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'status' => ['required', 'in:draft,active,expired,archived'],
            'event_date' => ['nullable', 'date'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'price_agreed' => ['nullable', 'integer', 'min:0'],
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'amount_paid' => ['nullable', 'integer', 'min:0'],
            'paid_at' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string'],
            'is_demo' => ['boolean'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:'.implode(',', \App\Support\SectionRegistry::keys())],
        ]);
    }

    /**
     * Keep the operator's order, drop empties/duplicates and force `welcome`
     * first (AGENTS.md 5.5). Feature filtering happens on read in
     * Invitation::resolvedSections().
     *
     * @param  list<string>  $sections
     * @return list<string>
     */
    private function normalizeSections(array $sections): array
    {
        $clean = [];

        foreach ($sections as $key) {
            if (is_string($key) && \App\Support\SectionRegistry::exists($key) && ! in_array($key, $clean, true)) {
                $clean[] = $key;
            }
        }

        if ($clean === []) {
            return \App\Support\SectionRegistry::DEFAULT_ORDER;
        }

        $clean = array_values(array_filter($clean, fn ($key) => $key !== 'welcome'));
        array_unshift($clean, 'welcome');

        return $clean;
    }

    /**
     * @return list<array{key: string, label: string, feature: ?string}>
     */
    private function sectionOptions(): array
    {
        return array_map(fn (string $key) => [
            'key' => $key,
            'label' => \App\Support\SectionRegistry::label($key),
            'feature' => \App\Support\SectionRegistry::requiredFeature($key),
        ], \App\Support\SectionRegistry::keys());
    }

    private function defaultContent(string $title): array
    {
        return [
            'hero' => [
                'title' => $title,
                'date_formatted' => null,
                'background_url' => null,
                'music_url' => null,
            ],
            'mempelai' => [
                'pria' => ['nama_lengkap' => '', 'nama_panggilan' => '', 'ayah' => '', 'ibu' => '', 'foto_url' => null],
                'wanita' => ['nama_lengkap' => '', 'nama_panggilan' => '', 'ayah' => '', 'ibu' => '', 'foto_url' => null],
            ],
            'acara' => [],
            'verses' => [],
            'gratitude_message' => null,
            'moodboard' => ['colors' => [], 'announcements' => []],
        ];
    }

    private function uniqueSlug(string $candidate): string
    {
        $slug = Str::slug($candidate);
        $base = $slug;
        $suffix = 2;

        while (Invitation::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function themeOptions()
    {
        return Theme::query()
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'is_active']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function packageOptions()
    {
        return Package::query()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'price']);
    }
}
