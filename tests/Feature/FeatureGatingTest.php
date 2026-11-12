<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P2-T1: package features gate routes and the UI (matrix T16).
 */
class FeatureGatingTest extends TestCase
{
    use RefreshDatabase;

    private function invitationWith(array $overrides, array $attributes = []): Invitation
    {
        return Invitation::factory()->create(array_merge([
            'status' => 'active',
            'feature_overrides' => $overrides,
        ], $attributes));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_photobooth_page_is_404_when_feature_is_off(): void
    {
        $invitation = $this->invitationWith(['photobooth' => false]);
        $guest = Guest::factory()->arrived()->create(['invitation_id' => $invitation->id]);

        $this->get('/'.$invitation->slug.'/photobooth?guest='.$guest->guest_code)
            ->assertNotFound();
    }

    public function test_photobooth_capture_is_403_json_when_feature_is_off(): void
    {
        $invitation = $this->invitationWith(['photobooth' => false]);
        $guest = Guest::factory()->arrived()->create(['invitation_id' => $invitation->id]);

        $this->postJson('/'.$invitation->slug.'/photobooth/capture', [
            'guest_code' => $guest->guest_code,
            'image' => 'data:image/jpeg;base64,AAAA',
        ])->assertForbidden();
    }

    public function test_public_page_reports_features_and_hides_photobooth_banner(): void
    {
        $invitation = $this->invitationWith(['photobooth' => false]);
        $guest = Guest::factory()->arrived()->create(['invitation_id' => $invitation->id]);

        $response = $this->get('/'.$invitation->slug.'?guest='.$guest->guest_code);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('features.photobooth', false)
            ->where('meta.photoboothUrl', null)
        );
    }

    public function test_scanner_is_403_without_guestbook_feature(): void
    {
        $invitation = $this->invitationWith(['guestbook' => false]);

        $this->actingAs($this->admin())
            ->withSession(['admin_invitation_id' => $invitation->id])
            ->get('/admin/scanner')
            ->assertForbidden();
    }

    public function test_export_is_403_without_guestbook_feature(): void
    {
        $invitation = $this->invitationWith(['guestbook' => false]);

        $this->actingAs($this->admin())
            ->withSession(['admin_invitation_id' => $invitation->id])
            ->get('/admin/guests/export')
            ->assertForbidden();
    }

    public function test_print_labels_is_403_without_qr_cards_feature(): void
    {
        $invitation = $this->invitationWith(['qr_cards' => false]);

        $this->actingAs($this->admin())
            ->withSession(['admin_invitation_id' => $invitation->id])
            ->get('/admin/guests/print-labels')
            ->assertForbidden();
    }

    public function test_memory_book_is_403_without_feature(): void
    {
        $invitation = $this->invitationWith(['memory_book' => false]);

        $this->actingAs($this->admin())
            ->withSession(['admin_invitation_id' => $invitation->id])
            ->get('/admin/photobooth/print')
            ->assertForbidden();
    }

    public function test_gallery_admin_is_403_without_gallery_feature(): void
    {
        $invitation = $this->invitationWith(['gallery' => false]);

        $this->actingAs($this->admin())
            ->withSession(['admin_invitation_id' => $invitation->id])
            ->get('/admin/gallery')
            ->assertForbidden();
    }

    public function test_all_features_are_on_without_a_package(): void
    {
        $invitation = Invitation::factory()->create(['status' => 'active']);

        $this->assertTrue($invitation->hasFeature('photobooth'));
        $this->assertTrue($invitation->hasFeature('memory_book'));
    }
}
