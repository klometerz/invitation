<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Support\SectionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P2-T3: section ordering and server-side feature filtering (K11).
 */
class SectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_used_when_sections_is_null(): void
    {
        $invitation = Invitation::factory()->create(['sections' => null]);

        $this->assertSame(SectionRegistry::DEFAULT_ORDER, $invitation->resolvedSections());
    }

    public function test_welcome_is_forced_first(): void
    {
        $invitation = Invitation::factory()->create([
            'sections' => ['event', 'welcome', 'thanks'],
        ]);

        $this->assertSame(['welcome', 'event', 'thanks'], $invitation->resolvedSections());
    }

    public function test_operator_order_is_kept(): void
    {
        $invitation = Invitation::factory()->create([
            'sections' => ['welcome', 'thanks', 'event'],
        ]);

        $this->assertSame(['welcome', 'thanks', 'event'], $invitation->resolvedSections());
    }

    public function test_gallery_section_is_removed_when_feature_is_off(): void
    {
        $invitation = Invitation::factory()->create([
            'sections' => ['welcome', 'gallery', 'rsvp'],
            'feature_overrides' => ['gallery' => false],
        ]);

        $this->assertSame(['welcome', 'rsvp'], $invitation->resolvedSections());
    }

    public function test_info_section_is_removed_when_feature_is_off(): void
    {
        $invitation = Invitation::factory()->create([
            'sections' => ['welcome', 'info', 'event'],
            'feature_overrides' => ['info_section' => false],
        ]);

        $this->assertSame(['welcome', 'event'], $invitation->resolvedSections());
    }

    public function test_rsvp_section_is_removed_when_feature_is_off(): void
    {
        $invitation = Invitation::factory()->create([
            'sections' => ['welcome', 'rsvp'],
            'feature_overrides' => ['rsvp' => false],
        ]);

        $this->assertSame(['welcome'], $invitation->resolvedSections());
    }

    public function test_unknown_and_duplicate_keys_are_dropped(): void
    {
        $invitation = Invitation::factory()->create([
            'sections' => ['welcome', 'bogus', 'event', 'event', 'thanks'],
        ]);

        $this->assertSame(['welcome', 'event', 'thanks'], $invitation->resolvedSections());
    }

    public function test_verses_section_is_optional_and_independent_of_features(): void
    {
        $invitation = Invitation::factory()->create([
            'sections' => ['welcome', 'event'],
        ]);

        $this->assertSame(['welcome', 'event'], $invitation->resolvedSections());
    }

    public function test_public_page_receives_resolved_sections(): void
    {
        $invitation = Invitation::factory()->create([
            'status' => 'active',
            'sections' => ['welcome', 'gallery', 'thanks'],
            'feature_overrides' => ['gallery' => false],
        ]);

        $this->get('/'.$invitation->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sections', ['welcome', 'thanks'])
            );
    }
}
