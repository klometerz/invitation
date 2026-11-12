<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * P1-T2: backfill the multi-tenant columns introduced in P1-T1.
 *
 * Rules honoured here:
 *  - raw DB::table() only, never Eloquent, so future global tenant scopes
 *    cannot interfere with the backfill;
 *  - chunked, so a large guests table is not loaded into memory;
 *  - idempotent, every write is guarded by a whereNull / where-not condition;
 *  - portable: no MySQL-only "UPDATE ... JOIN", because the test suite runs
 *    the whole migration set on sqlite :memory:.
 */
return new class extends Migration
{
    public function up(): void
    {
        $invitationId = DB::table('invitations')->min('id');

        if ($invitationId === null) {
            // Fresh install: nothing to backfill. New rows are handled by
            // the tenant scope (P1-T3) instead.
            $this->seedThemes();
            $this->seedPackages();

            return;
        }

        $this->assignGuests($invitationId);
        $this->assignGalleries($invitationId);
        $this->assignPhotoboothPhotos();
        $this->promoteExistingUsersToAdmin();
        $this->activateExistingInvitation($invitationId);

        $this->seedThemes();
        $this->seedPackages();
    }

    private function assignGuests(int $invitationId): void
    {
        DB::table('guests')
            ->whereNull('invitation_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($invitationId) {
                $ids = $rows->pluck('id')->all();

                DB::table('guests')
                    ->whereIn('id', $ids)
                    ->whereNull('invitation_id')
                    ->update(['invitation_id' => $invitationId]);
            });
    }

    private function assignGalleries(int $invitationId): void
    {
        DB::table('galleries')
            ->whereNull('invitation_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($invitationId) {
                $ids = $rows->pluck('id')->all();

                DB::table('galleries')
                    ->whereIn('id', $ids)
                    ->whereNull('invitation_id')
                    ->update(['invitation_id' => $invitationId]);
            });
    }

    /**
     * photobooth_photos has no direct tenant column historically, so the
     * tenant is derived through guest_id (audit finding C5).
     */
    private function assignPhotoboothPhotos(): void
    {
        DB::table('photobooth_photos')
            ->whereNull('invitation_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                $ids = $rows->pluck('id')->all();
                $guestIds = $rows->pluck('guest_id')->unique()->filter()->values()->all();

                if ($guestIds === []) {
                    return;
                }

                $tenants = DB::table('guests')
                    ->whereIn('id', $guestIds)
                    ->whereNotNull('invitation_id')
                    ->pluck('invitation_id', 'id');

                foreach ($rows as $row) {
                    $tenant = $tenants[$row->guest_id] ?? null;

                    if ($tenant === null) {
                        continue;
                    }

                    DB::table('photobooth_photos')
                        ->where('id', $row->id)
                        ->whereNull('invitation_id')
                        ->update(['invitation_id' => $tenant]);
                }
            });
    }

    /**
     * Every pre-existing account belongs to the owner, so it becomes admin.
     * New accounts created after this migration default to `staff` (P1-T1).
     */
    private function promoteExistingUsersToAdmin(): void
    {
        DB::table('users')
            ->where('role', '!=', 'admin')
            ->update(['role' => 'admin']);
    }

    private function activateExistingInvitation(int $invitationId): void
    {
        $invitation = DB::table('invitations')->where('id', $invitationId)->first();

        if ($invitation === null) {
            return;
        }

        $update = ['status' => 'active'];

        // event_date from content.acara[0].tanggal when it parses.
        if ($invitation->event_date === null) {
            $eventDate = $this->firstEventDate($invitation->content ?? null);

            if ($eventDate !== null) {
                $update['event_date'] = $eventDate;
            }
        }

        DB::table('invitations')->where('id', $invitationId)->update($update);
    }

    private function firstEventDate(?string $contentJson): ?string
    {
        if ($contentJson === null || $contentJson === '') {
            return null;
        }

        $content = json_decode($contentJson, true);

        if (! is_array($content)) {
            return null;
        }

        $raw = $content['acara'][0]['tanggal'] ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Backfill themes.key from the legacy slug, then make sure the
     * `heritage-gold` theme exists (AGENTS.md 5.8).
     */
    private function seedThemes(): void
    {
        DB::table('themes')
            ->whereNull('key')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('themes')
                        ->where('id', $row->id)
                        ->whereNull('key')
                        ->update(['key' => $row->slug]);
                }
            });

        $exists = DB::table('themes')->where('key', 'heritage-gold')->exists();

        if (! $exists) {
            $now = now();

            DB::table('themes')->insert([
                'key' => 'heritage-gold',
                'name' => 'Heritage Gold',
                'slug' => $this->uniqueThemeSlug('heritage-gold'),
                'preview_image' => null,
                'svelte_path' => 'Wedding',
                'is_premium' => false,
                'description' => 'TODO(owner): the original gold/black design, ported to resources/js/Themes/heritage-gold in P2-T6.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function uniqueThemeSlug(string $base): string
    {
        $slug = $base;
        $suffix = 1;

        while (DB::table('themes')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Placeholder packages. Price and contents are the owner's call
     * (AGENTS.md 5.4); every limit stays null (unlimited) so nothing is
     * silently restricted before P3-T1.
     */
    private function seedPackages(): void
    {
        $featureKeys = [
            'rsvp', 'gallery', 'music', 'info_section',
            'guestbook', 'qr_cards', 'photobooth', 'memory_book',
        ];

        $placeholders = [
            ['name' => 'Basic', 'slug' => 'basic', 'sort_order' => 1],
            ['name' => 'Plus', 'slug' => 'plus', 'sort_order' => 2],
            ['name' => 'Premium', 'slug' => 'premium', 'sort_order' => 3],
        ];

        foreach ($placeholders as $package) {
            if (DB::table('packages')->where('slug', $package['slug'])->exists()) {
                continue;
            }

            $now = now();

            DB::table('packages')->insert([
                'name' => $package['name'],
                'slug' => $package['slug'],
                'description' => 'TODO(owner): package contents to be defined in the Packages GUI (P3-T1).',
                'price' => null,
                'features' => json_encode(array_fill_keys($featureKeys, true)),
                'limits' => json_encode([
                    'max_guests' => null,
                    'max_gallery_images' => null,
                    'max_photobooth_per_guest' => null,
                    'active_days_after_event' => null,
                ]),
                'is_public' => true,
                'is_active' => true,
                'sort_order' => $package['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Data backfill is intentionally lossy on rollback: the values did
        // not exist before this migration, so there is nothing to restore.
        DB::table('photobooth_photos')->update(['invitation_id' => null]);
        DB::table('galleries')->update(['invitation_id' => null]);
        DB::table('guests')->update(['invitation_id' => null]);
        DB::table('users')->update(['role' => 'staff']);

        DB::table('themes')->whereIn('key', ['heritage-gold'])->delete();
        DB::table('packages')->whereIn('slug', ['basic', 'plus', 'premium'])->delete();
    }
};
