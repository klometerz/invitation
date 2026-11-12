<?php

namespace Database\Seeders;

use App\Models\Gallery;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\Package;
use App\Models\Theme;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * P3-T7: idempotent demo invitation (slug `demo`, is_demo = true).
 *
 * Content is invented on purpose (fake couple, fake guests), assets are local
 * generated SVGs under public/demo-assets (no hotlinking; `/demo` is a route, not a folder), and every feature is on.
 * Running it twice restores the same state.
 */
class DemoInvitationSeeder extends Seeder
{
    public const SLUG = 'demo';

    public function run(): void
    {
        $context = app(TenantContext::class);

        // Seeder context: no request tenant is set, so the fail-closed scope
        // must be bypassed deliberately for every query below.
        $context->withoutScope(function () {
            $owner = User::query()->where('role', User::ROLE_ADMIN)->orderBy('id')->first()
                ?? User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

            $theme = Theme::query()->where('key', 'heritage-gold')->first()
                ?? Theme::query()->orderBy('id')->firstOrFail();

            $package = Package::query()->where('slug', 'premium')->first()
                ?? Package::query()->orderBy('id')->first();

            $eventDate = Carbon::now()->addMonths(2)->startOfMonth()->addDays(5);

            $payload = [
                'user_id' => $owner->id,
                'theme_id' => $theme->id,
                'package_id' => $package?->id,
                'slug' => self::SLUG,
                'title' => 'The Wedding of Demo & Pasangan (Contoh)',
                'status' => 'active',
                'is_active' => true,
                'is_demo' => true,
                'feature_overrides' => null,
                'event_date' => $eventDate->toDateString(),
                'active_from' => Carbon::now()->subDay(),
                'active_until' => Carbon::now()->addMonths(6),
                'client_name' => 'Undangan Contoh',
                'sections' => ['welcome', 'verses', 'event', 'info', 'gallery', 'rsvp', 'thanks'],
                'content' => $this->content($eventDate),
            ];

            $invitation = Invitation::query()->where('slug', self::SLUG)->first();

            if ($invitation === null) {
                $invitation = Invitation::create($payload);
            } else {
                $invitation->forceFill($payload)->save();
            }

            $this->resetTenantData($invitation);

            $this->seedGallery($invitation);
            $this->seedGuests($invitation);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function content(Carbon $eventDate): array
    {
        $dateText = $eventDate->format('d . m . Y');

        return [
            'hero' => [
                'title' => 'THE WEDDING CELEBRATION',
                'date_formatted' => strtoupper($eventDate->translatedFormat('l, d F Y')),
                'background_url' => '/demo-assets/hero.svg',
                'music_url' => null,
                'hashtag' => '#DemoWedding',
            ],
            'logo_url' => '/demo-assets/logo.svg',
            'loading_tips' => [
                'Ini undangan contoh untuk demonstrasi.',
                'Semua data di sini fiktif.',
                'Coba konfirmasi kehadiran di bagian RSVP.',
            ],
            'mempelai' => [
                'wanita' => [
                    'nama_lengkap' => 'Pasangan Pertama',
                    'nama_panggilan' => 'Pasangan',
                    'ayah' => 'Bapak Contoh',
                    'ibu' => 'Ibu Contoh',
                    'foto_url' => null,
                ],
                'pria' => [
                    'nama_lengkap' => 'Pasangan Kedua',
                    'nama_panggilan' => 'Kedua',
                    'ayah' => 'Bapak Contoh',
                    'ibu' => 'Ibu Contoh',
                    'foto_url' => null,
                ],
            ],
            'acara' => [
                [
                    'tipe' => 'akad',
                    'tanggal' => $eventDate->toDateString(),
                    'jam' => '09.00 - 11.00',
                    'lokasi' => 'Contoh Lokasi Akad',
                    'alamat' => 'Alamat contoh untuk demonstrasi',
                    'map_url' => 'https://maps.google.com/?q=-6.2,106.8',
                ],
                [
                    'tipe' => 'resepsi',
                    'tanggal' => $eventDate->toDateString(),
                    'jam' => '11.00 - 14.00',
                    'lokasi' => 'Contoh Lokasi Resepsi',
                    'alamat' => 'Alamat contoh untuk demonstrasi',
                    'map_url' => 'https://maps.google.com/?q=-6.2,106.8',
                ],
            ],
            'verses' => [],
            'gratitude_message' => 'Dengan penuh syukur kami mengundang Anda hadir di acara pernikahan kami.',
            'moodboard' => [
                'colors' => [
                    ['hex' => '#D4AF37', 'name' => 'Gold'],
                    ['hex' => '#111111', 'name' => 'Charcoal'],
                ],
                'announcements' => [
                    [
                        'icon' => 'invitation',
                        'title' => 'Check-in dengan QR',
                        'description' => 'Tunjukkan QR pribadi Anda di pintu masuk.',
                    ],
                ],
            ],
            'thanks' => [
                'bank_accounts' => [],
                'qris_image_url' => null,
            ],
            'invite_message_template' => "Halo {name},\n\nIni undangan contoh kami:\n{link}\n\nSalam,\n{couple}",
        ];
    }

    private function resetTenantData(Invitation $invitation): void
    {
        DB::table('photobooth_photos')->where('invitation_id', $invitation->id)->delete();
        DB::table('guests')->where('invitation_id', $invitation->id)->delete();
        DB::table('galleries')->where('invitation_id', $invitation->id)->delete();
    }

    private function seedGallery(Invitation $invitation): void
    {
        $images = [
            '/demo-assets/gallery-1.svg',
            '/demo-assets/gallery-2.svg',
            '/demo-assets/gallery-3.svg',
            '/demo-assets/gallery-4.svg',
        ];

        foreach ($images as $index => $path) {
            Gallery::create([
                'invitation_id' => $invitation->id,
                'filename' => basename($path),
                'path' => $path,
                'order' => $index,
            ]);
        }
    }

    private function seedGuests(Invitation $invitation): void
    {
        $names = [
            'Tamu Demo Satu',
            'Tamu Demo Dua',
            'Tamu Demo Tiga',
            'Tamu Demo Check-in',
        ];

        foreach ($names as $index => $name) {
            $guest = Guest::create([
                'invitation_id' => $invitation->id,
                'name' => $name,
                'city' => 'Jakarta',
                'category' => 'Tamu Demo',
                'quota' => $index === 0 ? 2 : 1,
            ]);

            // A pre-checked-in guest with a visible demo link.
            if ($name === 'Tamu Demo Check-in') {
                $guest->update([
                    'checkin_status' => 'arrived',
                    'checked_in_at' => now(),
                    'actual_guest_count' => 1,
                    'checked_in_by' => 'Demo',
                ]);
            }
        }
    }
}
