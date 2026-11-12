<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Phase 3: lifecycle, limits, hardening, settings and landing (matrix
 * T15, T17, T18, T19 + package/settings coverage).
 */
class Phase3Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    }

    private function asAdmin(): self
    {
        return $this->actingAs($this->admin());
    }

    // ---------------------------------------------------------------- T15
    public function test_expired_invitation_shows_expired_page(): void
    {
        $invitation = Invitation::factory()->expired()->create();

        $response = $this->get('/'.$invitation->slug);

        $response->assertStatus(410);
        $response->assertInertia(fn ($page) => $page->component('Invitation/Expired'));
    }

    public function test_expired_invitation_rejects_rsvp(): void
    {
        $invitation = Invitation::factory()->expired()->create();
        $guest = Guest::factory()->create(['invitation_id' => $invitation->id]);

        $this->postJson('/api/rsvp/'.$guest->guest_code, ['status' => 'confirmed'])
            ->assertForbidden();

        $this->assertFalse((bool) $guest->fresh()->is_rsvp);
    }

    public function test_active_invitation_accepts_rsvp(): void
    {
        $invitation = Invitation::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->create(['invitation_id' => $invitation->id]);

        $this->postJson('/api/rsvp/'.$guest->guest_code, ['status' => 'confirmed'])
            ->assertOk();

        $this->assertTrue((bool) $guest->fresh()->is_rsvp);
    }

    public function test_activate_stamps_the_window(): void
    {
        $package = Package::create([
            'name' => 'P', 'slug' => 'p',
            'features' => ['photobooth' => true],
            'limits' => ['active_days_after_event' => 7],
        ]);

        $invitation = Invitation::factory()->draft()->create([
            'package_id' => $package->id,
            'event_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->asAdmin()->post('/admin/invitations/'.$invitation->id.'/activate')->assertRedirect();

        $invitation->refresh();
        $this->assertSame('active', $invitation->status);
        $this->assertNotNull($invitation->active_from);
        $this->assertSame(
            now()->addDays(10)->endOfDay()->addDays(7)->toDateString(),
            $invitation->active_until?->toDateString()
        );
    }

    public function test_status_transitions_are_guarded(): void
    {
        $archived = Invitation::factory()->archived()->create();

        $this->asAdmin()
            ->put('/admin/invitations/'.$archived->id, [
                'title' => $archived->title,
                'slug' => $archived->slug,
                'theme_id' => $archived->theme_id,
                'status' => 'active',
                'payment_status' => 'unpaid',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('archived', $archived->fresh()->status);
    }

    public function test_expire_command_flips_past_due_active_invitations(): void
    {
        $due = Invitation::factory()->create([
            'status' => 'active',
            'active_until' => now()->subHour(),
        ]);
        $future = Invitation::factory()->create([
            'status' => 'active',
            'active_until' => now()->addDay(),
        ]);

        Artisan::call('invitations:expire');

        $this->assertSame('expired', $due->fresh()->status);
        $this->assertSame('active', $future->fresh()->status);
    }

    public function test_expire_command_dry_run_writes_nothing(): void
    {
        $due = Invitation::factory()->create([
            'status' => 'active',
            'active_until' => now()->subHour(),
        ]);

        Artisan::call('invitations:expire', ['--dry-run' => true]);

        $this->assertSame('active', $due->fresh()->status);
    }

    public function test_purge_is_dry_run_by_default(): void
    {
        $invitation = Invitation::factory()->archived()->create();
        $invitation->forceFill(['updated_at' => now()->subDays(400)])->save();
        Guest::factory()->count(2)->create(['invitation_id' => $invitation->id]);

        Artisan::call('invitations:purge');

        $this->assertSame(2, Guest::withoutGlobalScopes()->where('invitation_id', $invitation->id)->count());
    }

    public function test_purge_with_force_removes_archived_guest_data(): void
    {
        $invitation = Invitation::factory()->archived()->create();
        $invitation->forceFill(['updated_at' => now()->subDays(400)])->save();
        Guest::factory()->count(2)->create(['invitation_id' => $invitation->id]);

        $keep = Invitation::factory()->create(['status' => 'active']);
        Guest::factory()->create(['invitation_id' => $keep->id]);

        Artisan::call('invitations:purge', ['--force' => true]);

        $this->assertSame(0, Guest::withoutGlobalScopes()->where('invitation_id', $invitation->id)->count());
        $this->assertSame(1, Guest::withoutGlobalScopes()->where('invitation_id', $keep->id)->count());
    }

    // ---------------------------------------------------------------- T17
    public function test_max_guests_limit_blocks_create(): void
    {
        $package = Package::create([
            'name' => 'P', 'slug' => 'p-limit',
            'features' => ['rsvp' => true],
            'limits' => ['max_guests' => 1],
        ]);

        $invitation = Invitation::factory()->create(['status' => 'active', 'package_id' => $package->id]);
        Guest::factory()->create(['invitation_id' => $invitation->id]);

        $this->actingAs($this->admin())
            ->withSession(['admin_invitation_id' => $invitation->id])
            ->post('/admin/guests', ['name' => 'Tamu Baru', 'quota' => 1])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Guest::withoutGlobalScopes()->where('invitation_id', $invitation->id)->count());
    }

    public function test_max_photobooth_per_guest_limit(): void
    {
        $package = Package::create([
            'name' => 'P', 'slug' => 'p-photo',
            'features' => ['photobooth' => true],
            'limits' => ['max_photobooth_per_guest' => 0],
        ]);

        $invitation = Invitation::factory()->create(['status' => 'active', 'package_id' => $package->id]);
        $guest = Guest::factory()->arrived()->create(['invitation_id' => $invitation->id]);

        $this->postJson('/'.$invitation->slug.'/photobooth/capture', [
            'guest_code' => $guest->guest_code,
            'image' => 'data:image/png;base64,'.base64_encode($this->pngBytes()),
        ])->assertStatus(422);
    }

    // ---------------------------------------------------------------- T18
    public function test_rsvp_is_rate_limited(): void
    {
        $invitation = Invitation::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->create(['invitation_id' => $invitation->id]);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/rsvp/'.$guest->guest_code, ['status' => 'confirmed']);
        }

        $this->postJson('/api/rsvp/'.$guest->guest_code, ['status' => 'confirmed'])
            ->assertStatus(429);
    }

    // ---------------------------------------------------------------- T19
    public function test_oversized_base64_is_rejected(): void
    {
        $invitation = Invitation::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->arrived()->create(['invitation_id' => $invitation->id]);

        $this->postJson('/'.$invitation->slug.'/photobooth/capture', [
            'guest_code' => $guest->guest_code,
            'image' => 'data:image/png;base64,'.str_repeat('A', 4_100_000),
        ])->assertStatus(422);
    }

    public function test_non_image_base64_is_rejected(): void
    {
        $invitation = Invitation::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->arrived()->create(['invitation_id' => $invitation->id]);

        $this->postJson('/'.$invitation->slug.'/photobooth/capture', [
            'guest_code' => $guest->guest_code,
            'image' => 'data:image/png;base64,'.base64_encode('<?php echo "pwned"; ?>'),
        ])->assertStatus(422);
    }

    public function test_valid_image_is_accepted_and_stored_as_jpeg(): void
    {
        $invitation = Invitation::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->arrived()->create(['invitation_id' => $invitation->id]);

        $response = $this->postJson('/'.$invitation->slug.'/photobooth/capture', [
            'guest_code' => $guest->guest_code,
            'image' => 'data:image/png;base64,'.base64_encode($this->pngBytes()),
        ]);

        $response->assertOk();
        $this->assertStringEndsWith('.jpg', $response->json('photo.image_path'));
    }

    public function test_checkin_uses_the_authenticated_user(): void
    {
        $invitation = Invitation::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->create(['invitation_id' => $invitation->id]);
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'invitation_id' => $invitation->id,
            'name' => 'Petugas Resmi',
            'is_active' => true,
        ]);

        $this->actingAs($staff)->postJson('/admin/scanner/checkin', [
            'code' => $guest->guest_code,
            'checked_in_by' => 'Attacker Name',
        ])->assertOk();

        $this->assertSame('Petugas Resmi', $guest->fresh()->checked_in_by);
    }

    // -------------------------------------------------------------- settings
    public function test_settings_are_saved_and_shared(): void
    {
        $this->asAdmin()->post('/admin/settings', [
            'business_name' => 'Undangan Kita',
            'whatsapp_number' => '628123456789',
            'instagram_url' => 'https://instagram.com/x',
            'footer_text' => 'Footer',
        ])->assertRedirect();

        $this->assertSame('Undangan Kita', \App\Support\Settings::get('business_name'));

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Landing')
            ->where('platform.business_name', 'Undangan Kita')
            ->where('whatsappUrl', 'https://wa.me/628123456789?text='.rawurlencode('Halo, saya ingin bertanya tentang undangan pernikahan digital.'))
        );
    }

    public function test_landing_shows_hubungi_kami_for_null_price(): void
    {
        Package::create([
            'name' => 'Basic', 'slug' => 'basic-x',
            'price' => null, 'features' => [], 'limits' => [],
            'is_public' => true, 'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Landing')
            ->where('packages.0.price', null)
        );
    }

    public function test_legal_pages_render_placeholders(): void
    {
        $this->get('/terms')->assertOk()->assertInertia(fn ($page) => $page->component('Legal/Terms'));
        $this->get('/privacy')->assertOk()->assertInertia(fn ($page) => $page->component('Legal/Privacy'));
    }

    public function test_whatsapp_cta_is_null_without_a_number(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->where('whatsappUrl', null));
    }

    // ------------------------------------------------------------ packages
    public function test_package_crud_roundtrip(): void
    {
        $this->asAdmin()->post('/admin/packages', [
            'name' => 'Premium',
            'slug' => 'premium-test',
            'price' => 1500000,
            'features' => ['photobooth', 'memory_book'],
            'limits' => ['max_guests' => 300, 'max_gallery_images' => ''],
            'is_public' => true,
            'is_active' => true,
            'sort_order' => 1,
        ])->assertRedirect();

        $package = Package::where('slug', 'premium-test')->firstOrFail();

        $this->assertTrue($package->featureFlags()['photobooth']);
        $this->assertFalse($package->featureFlags()['rsvp']);
        $this->assertSame(300, $package->limitValues()['max_guests']);
        $this->assertNull($package->limitValues()['max_gallery_images']);

        $this->asAdmin()->put('/admin/packages/'.$package->id, [
            'name' => 'Premium Plus',
            'slug' => 'premium-test',
            'price' => null,
            'features' => ['photobooth'],
            'limits' => [],
            'is_public' => false,
            'is_active' => true,
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertSame('Premium Plus', $package->fresh()->name);
        $this->assertNull($package->fresh()->price);
    }

    // ----------------------------------------------------------------- demo
    public function test_demo_seeder_is_idempotent_and_flagged(): void
    {
        $this->seed(\Database\Seeders\DemoInvitationSeeder::class);
        $this->seed(\Database\Seeders\DemoInvitationSeeder::class);

        $demos = Invitation::where('slug', 'demo')->get();

        $this->assertCount(1, $demos);
        $this->assertTrue((bool) $demos->first()->is_demo);
    }

    public function test_demo_invitation_shows_demo_banner(): void
    {
        $this->seed(\Database\Seeders\DemoInvitationSeeder::class);

        $this->get('/demo')->assertOk()->assertInertia(fn ($page) => $page->where('isDemo', true));
    }

    /**
     * A minimal valid 2x2 PNG, used to exercise the capture pipeline.
     */
    private function pngBytes(): string
    {
        $image = imagecreatetruecolor(2, 2);
        $gold = imagecolorallocate($image, 212, 175, 55);
        imagefilledrectangle($image, 0, 0, 2, 2, $gold);

        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();

        imagedestroy($image);

        return $bytes;
    }
}
