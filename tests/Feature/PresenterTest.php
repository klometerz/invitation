<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Guest;
use App\Models\Invitation;
use App\Support\InvitationPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P2-T2 / P2-T5 / P2-T8: the presenter is the only source of derived values,
 * and the public payload contains no raw model fields (T20).
 */
class PresenterTest extends TestCase
{
    use RefreshDatabase;

    private function invitation(array $content = [], array $attributes = []): Invitation
    {
        $invitation = Invitation::factory()->create($attributes);

        if ($content !== []) {
            $invitation->update(['content' => array_merge($invitation->content, $content)]);
        }

        return $invitation->fresh();
    }

    public function test_couple_names_are_woman_first(): void
    {
        $invitation = $this->invitation([
            'mempelai' => [
                'wanita' => ['nama_panggilan' => 'Maul'],
                'pria' => ['nama_panggilan' => 'Kahfi'],
            ],
        ]);

        $this->assertSame('Maul & Kahfi', InvitationPresenter::for($invitation)->coupleNames());
    }

    public function test_couple_names_are_empty_when_content_is_missing(): void
    {
        $invitation = $this->invitation([
            'mempelai' => ['wanita' => [], 'pria' => []],
        ]);

        $this->assertSame('', InvitationPresenter::for($invitation)->coupleNames());
    }

    public function test_date_text_comes_from_the_first_event(): void
    {
        $invitation = $this->invitation([
            'acara' => [['tanggal' => '2026-09-06']],
        ]);

        $this->assertSame('06 . 09 . 2026', InvitationPresenter::for($invitation)->dateText());
    }

    public function test_date_text_falls_back_to_event_date_column(): void
    {
        $invitation = Invitation::factory()->create(['event_date' => '2027-01-02']);

        $this->assertSame('02 . 01 . 2027', InvitationPresenter::for($invitation)->dateText());
    }

    public function test_date_text_is_empty_without_any_date(): void
    {
        $invitation = Invitation::factory()->create([
            'event_date' => null,
            'content' => ['hero' => [], 'acara' => [], 'mempelai' => []],
        ]);

        $this->assertSame('', InvitationPresenter::for($invitation)->dateText());
    }

    public function test_og_image_prefers_hero_then_gallery_then_null(): void
    {
        $invitation = $this->invitation([
            'hero' => ['background_url' => '/storage/hero.jpg'],
        ]);

        $this->assertSame(url('/storage/hero.jpg'), InvitationPresenter::for($invitation)->ogImage());

        $invitation->update(['content' => ['hero' => []]]);
        Gallery::factory()->create([
            'invitation_id' => $invitation->id,
            'path' => '/storage/gallery/first.jpg',
        ]);

        $this->assertSame(
            url('/storage/gallery/first.jpg'),
            InvitationPresenter::for($invitation->fresh())->ogImage()
        );

        // Explicit unscoped delete: the tenant scope fails closed outside a
        // request, which is the behaviour under test elsewhere.
        Gallery::withoutGlobalScopes()->where('invitation_id', $invitation->id)->delete();

        $this->assertNull(InvitationPresenter::for($invitation->fresh())->ogImage());
    }

    public function test_seo_is_per_invitation_and_absolute(): void
    {
        $invitation = Invitation::factory()->create(['title' => 'Judul Klien']);

        $seo = InvitationPresenter::for($invitation)->seo();

        $this->assertStringContainsString('Judul Klien', $seo['title']);
        $this->assertSame(url('/'.$invitation->slug), $seo['url']);
    }

    public function test_invite_template_has_a_default_and_renders_placeholders(): void
    {
        $invitation = $this->invitation([
            'mempelai' => [
                'wanita' => ['nama_panggilan' => 'Maul'],
                'pria' => ['nama_panggilan' => 'Kahfi'],
            ],
        ]);

        $presenter = InvitationPresenter::for($invitation);
        $template = $presenter->inviteMessageTemplate();

        $this->assertStringContainsString('{name}', $template);
        $this->assertStringContainsString('{link}', $template);

        $rendered = $presenter->renderInviteMessage($template, 'Budi', 'https://x.test/a', 2);

        $this->assertStringContainsString('Budi', $rendered);
        $this->assertStringContainsString('https://x.test/a', $rendered);
        $this->assertStringContainsString('Maul & Kahfi', $rendered);
        $this->assertStringNotContainsString('{name}', $rendered);
    }

    public function test_custom_template_is_used_when_present(): void
    {
        $invitation = $this->invitation([
            'invite_message_template' => 'Halo {name}, buka {link}',
        ]);

        $this->assertSame(
            'Halo {name}, buka {link}',
            InvitationPresenter::for($invitation)->inviteMessageTemplate()
        );
    }

    public function test_photobooth_url_respects_the_feature_flag(): void
    {
        $on = Invitation::factory()->create();
        $off = Invitation::factory()->create(['feature_overrides' => ['photobooth' => false]]);

        $this->assertSame(
            url('/'.$on->slug.'/photobooth'),
            InvitationPresenter::for($on)->photoboothUrl()
        );
        $this->assertNull(InvitationPresenter::for($off)->photoboothUrl());
    }

    public function test_thanks_only_contains_real_data(): void
    {
        $invitation = Invitation::factory()->create();

        $this->assertSame(
            ['bank_accounts' => [], 'qris_image_url' => null],
            InvitationPresenter::for($invitation)->thanks()
        );
    }

    public function test_public_payload_is_whitelisted(): void
    {
        $invitation = Invitation::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->create([
            'invitation_id' => $invitation->id,
            'checked_in_by' => 'Petugas',
        ]);

        $response = $this->get('/'.$invitation->slug.'?guest='.$guest->guest_code);

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $invitation = $page->toArray()['props']['invitation'];
            $guest = $page->toArray()['props']['guestData'];

            // No raw columns leak to the browser (T20).
            $this->assertSame(['id', 'title', 'slug'], array_keys($invitation));
            $this->assertArrayNotHasKey('user_id', $guest);
            $this->assertArrayNotHasKey('invitation_id', $guest);
            $this->assertArrayNotHasKey('checked_in_by', $guest);
            $this->assertArrayNotHasKey('id', $guest);
        });
    }
}
