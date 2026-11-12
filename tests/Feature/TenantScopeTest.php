<?php

namespace Tests\Feature;

use App\Exceptions\TenantContextMissingException;
use App\Models\Gallery;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\PhotoboothPhoto;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1-T3: the fail-closed tenant scope on Guest / Gallery / PhotoboothPhoto.
 *
 * Rows are created with an explicit `invitation_id` while no tenant is
 * selected, which the trait allows (the tenant is known); the scope itself is
 * what must fail closed.
 */
class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = app(TenantContext::class);
        $this->context->forget();
    }

    private function invitation(): Invitation
    {
        return Invitation::factory()->create();
    }

    private function guestFor(Invitation $invitation, int $count = 1): void
    {
        Guest::factory()->count($count)->create(['invitation_id' => $invitation->id]);
    }

    public function test_no_context_returns_no_rows(): void
    {
        $a = $this->invitation();
        $this->guestFor($a, 3);

        $this->assertSame(3, Guest::withoutGlobalScopes()->count());
        $this->assertSame(0, Guest::count(), 'Scoped query must fail closed.');
        $this->assertCount(0, Guest::all());
    }

    public function test_create_without_tenant_throws(): void
    {
        $this->expectException(TenantContextMissingException::class);

        Guest::create(['name' => 'Budi Santoso']);
    }

    public function test_create_uses_the_active_tenant(): void
    {
        $a = $this->invitation();
        $this->context->set($a->id);

        $guest = Guest::create(['name' => 'Budi Santoso']);

        $this->assertSame($a->id, $guest->invitation_id);
        $this->assertSame(1, Guest::count());
    }

    public function test_create_for_another_tenant_throws(): void
    {
        $a = $this->invitation();
        $b = $this->invitation();
        $this->context->set($a->id);

        $this->expectException(TenantContextMissingException::class);

        Guest::create(['name' => 'Budi Santoso', 'invitation_id' => $b->id]);
    }

    public function test_scope_switches_with_the_context(): void
    {
        $a = $this->invitation();
        $b = $this->invitation();
        $this->guestFor($a, 2);
        $this->guestFor($b, 3);

        $this->context->set($a->id);
        $this->assertSame(2, Guest::count());

        $seenInB = $this->context->runAs($b->id, fn () => Guest::count());
        $this->assertSame(3, $seenInB);

        // Context must be restored after runAs().
        $this->assertSame($a->id, $this->context->id());
        $this->assertSame(2, Guest::count());
    }

    public function test_without_scope_sees_every_tenant(): void
    {
        $a = $this->invitation();
        $b = $this->invitation();
        $this->guestFor($a, 2);
        $this->guestFor($b, 3);

        $this->context->set($a->id);

        $total = $this->context->withoutScope(fn () => Guest::count());

        $this->assertSame(5, $total);
        $this->assertSame(2, Guest::count());
    }

    public function test_find_returns_null_for_another_tenants_row(): void
    {
        $a = $this->invitation();
        $b = $this->invitation();
        $guestB = Guest::factory()->create(['invitation_id' => $b->id]);

        $this->context->set($a->id);

        $this->assertNull(Guest::find($guestB->id));
        $this->assertNotNull(
            $this->context->withoutScope(fn () => Guest::find($guestB->id))
        );
    }

    public function test_gallery_is_tenant_scoped(): void
    {
        $a = $this->invitation();
        $b = $this->invitation();
        Gallery::factory()->count(2)->create(['invitation_id' => $a->id]);
        Gallery::factory()->create(['invitation_id' => $b->id]);

        $this->context->set($a->id);
        $this->assertSame(2, Gallery::count());

        $this->context->set($b->id);
        $this->assertSame(1, Gallery::count());

        $this->context->forget();
        $this->assertSame(0, Gallery::count());
    }

    public function test_photobooth_photo_is_tenant_scoped(): void
    {
        $a = $this->invitation();
        $b = $this->invitation();
        $guestA = Guest::factory()->create(['invitation_id' => $a->id]);
        $guestB = Guest::factory()->create(['invitation_id' => $b->id]);

        PhotoboothPhoto::factory()->count(2)->create([
            'invitation_id' => $a->id,
            'guest_id' => $guestA->id,
        ]);
        PhotoboothPhoto::factory()->create([
            'invitation_id' => $b->id,
            'guest_id' => $guestB->id,
        ]);

        $this->context->set($a->id);
        $this->assertSame(2, PhotoboothPhoto::count());

        $this->context->set($b->id);
        $this->assertSame(1, PhotoboothPhoto::count());

        $this->context->forget();
        $this->assertSame(0, PhotoboothPhoto::count());
    }

    public function test_photobooth_photo_create_without_tenant_throws(): void
    {
        $a = $this->invitation();
        $guest = Guest::factory()->create(['invitation_id' => $a->id]);

        $this->expectException(TenantContextMissingException::class);

        PhotoboothPhoto::create([
            'guest_id' => $guest->id,
            'image_path' => '/storage/photobooth/x.jpg',
        ]);
    }
}
