<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\PhotoboothPhoto;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * P1-T9: isolation/security matrix rows T1-T14 (AGENTS.md section 9).
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Invitation $a;

    private Invitation $b;

    private User $admin;

    private User $staffA;

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantContext::class)->forget();

        $this->a = Invitation::factory()->create(['title' => 'Wedding A']);
        $this->b = Invitation::factory()->create(['title' => 'Wedding B']);

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->staffA = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'invitation_id' => $this->a->id,
            'is_active' => true,
        ]);
    }

    private function guest(Invitation $invitation, string $name = 'Tamu'): Guest
    {
        return Guest::factory()->create([
            'invitation_id' => $invitation->id,
            'name' => $name,
        ]);
    }

    private function asAdminIn(Invitation $invitation): self
    {
        return $this->actingAs($this->admin)
            ->withSession(['admin_invitation_id' => $invitation->id]);
    }

    // T1 - no tenant context selects nothing.
    public function test_t1_no_context_returns_no_tenant_rows(): void
    {
        $this->guest($this->a);
        $this->guest($this->b);

        $this->assertSame(0, Guest::count());
        $this->assertSame(0, Gallery::count());
        $this->assertSame(0, PhotoboothPhoto::count());
    }

    // T2 - creating without a tenant throws.
    public function test_t2_create_without_context_throws(): void
    {
        $this->expectException(\App\Exceptions\TenantContextMissingException::class);

        Guest::create(['name' => 'Tanpa Tenant']);
    }

    // T3 - admin selects A and only sees A's guests.
    public function test_t3_admin_list_shows_only_selected_tenant(): void
    {
        $this->guest($this->a, 'Guest A');
        $this->guest($this->b, 'Guest B');

        $response = $this->asAdminIn($this->a)->get('/admin/guests');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/GuestIndex')
            ->has('guests.data', 1)
            ->where('guests.data.0.name', 'Guest A')
        );
    }

    // T4 - cross-tenant guest ids are invisible.
    public function test_t4_cross_tenant_guest_id_returns_404(): void
    {
        $guestB = $this->guest($this->b, 'Guest B');

        $this->asAdminIn($this->a)
            ->put("/admin/guests/{$guestB->id}", [
                'name' => 'Hijacked',
                'quota' => 1,
            ])
            ->assertNotFound();

        $this->asAdminIn($this->a)
            ->delete("/admin/guests/{$guestB->id}")
            ->assertNotFound();

        $this->assertSame('Guest B', $guestB->fresh()->name);
    }

    // T5 - a guest code from another invitation is not shown.
    public function test_t5_guest_code_of_other_tenant_is_not_shown(): void
    {
        $guestB = $this->guest($this->b, 'Guest B');

        $response = $this->get('/'.$this->a->slug.'?guest='.$guestB->guest_code);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Invitation/Show')
            ->where('guestData', null)
        );
    }

    // T6 - staff of A cannot check in B's guest.
    public function test_t6_staff_cannot_scan_other_tenant_guest(): void
    {
        $guestB = $this->guest($this->b, 'Guest B');

        $this->actingAs($this->staffA)
            ->postJson('/admin/scanner/checkin', ['code' => $guestB->guest_code])
            ->assertNotFound();

        $this->assertSame('not_arrived', $guestB->fresh()->checkin_status);
    }

    // T7 - staff has no access to admin-only screens.
    public function test_t7_staff_is_forbidden_from_admin_screens(): void
    {
        $this->actingAs($this->staffA)->get('/admin/guests')->assertForbidden();
        $this->actingAs($this->staffA)->get('/admin/gallery')->assertForbidden();
        $this->actingAs($this->staffA)->get('/admin/invitations')->assertForbidden();
        $this->actingAs($this->staffA)->get('/admin/users')->assertForbidden();
    }

    // T8 - import in A with a name that also exists in B leaves B untouched.
    public function test_t8_import_does_not_touch_other_tenant(): void
    {
        $guestB = Guest::factory()->create([
            'invitation_id' => $this->b->id,
            'name' => 'Budi',
            'city' => 'Bandung',
            'category' => 'Tamu B',
            'quota' => 5,
        ]);

        $csv = "name,city,category,quota\nBudi,Jakarta,Tamu A,2\n";

        // createWithContent() sets the client mime type from the extension, so
        // the `mimes:xlsx,xls,csv` rule behaves like a real browser upload.
        $file = UploadedFile::fake()->createWithContent('guests.csv', $csv);

        $this->asAdminIn($this->a)
            ->post('/admin/guests/import', ['file' => $file])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $guestB->refresh();
        $this->assertSame('Bandung', $guestB->city, 'Tenant B must not be modified.');
        $this->assertSame('Tamu B', $guestB->category);
        $this->assertSame(5, $guestB->quota);

        $this->assertDatabaseHas('guests', [
            'invitation_id' => $this->a->id,
            'name' => 'Budi',
            'city' => 'Jakarta',
        ]);
    }

    // T9 - guest reset only clears the current tenant and needs the slug.
    public function test_t9_reset_only_clears_current_tenant(): void
    {
        $this->guest($this->a, 'A1');
        $this->guest($this->a, 'A2');
        $guestB = $this->guest($this->b, 'B1');

        // Wrong slug: nothing is deleted.
        $this->asAdminIn($this->a)
            ->delete('/admin/guests/reset', ['confirm_slug' => 'wrong-slug'])
            ->assertSessionHasErrors('confirm_slug');

        $this->assertSame(2, Guest::withoutGlobalScopes()->where('invitation_id', $this->a->id)->count());

        // Correct slug: only A is cleared.
        $this->asAdminIn($this->a)
            ->delete('/admin/guests/reset', ['confirm_slug' => $this->a->slug])
            ->assertRedirect();

        $this->assertSame(0, Guest::withoutGlobalScopes()->where('invitation_id', $this->a->id)->count());
        $this->assertNotNull($guestB->fresh(), 'Tenant B must be untouched.');
    }

    // T10 - exports/print lists only current tenant rows.
    public function test_t10_exports_and_print_lists_are_scoped(): void
    {
        $guestA = $this->guest($this->a, 'Guest A');
        $guestB = $this->guest($this->b, 'Guest B');

        PhotoboothPhoto::factory()->create([
            'invitation_id' => $this->a->id,
            'guest_id' => $guestA->id,
        ]);

        $labels = $this->asAdminIn($this->a)->get('/admin/guests/print-labels');
        $labels->assertOk();
        $labels->assertInertia(fn ($page) => $page
            ->has('guests', 1)
            ->where('guests.0.name', 'Guest A')
        );

        $print = $this->asAdminIn($this->a)->get('/admin/photobooth/print');
        $print->assertOk();
        $print->assertInertia(fn ($page) => $page->has('photos', 1));

        // A guest id from tenant B must not appear in A's label sheet.
        $crossed = $this->asAdminIn($this->a)
            ->get('/admin/guests/print-labels?id='.$guestB->id);
        $crossed->assertOk();
        $crossed->assertInertia(fn ($page) => $page->has('guests', 0));
    }

    // T11 - legacy links redirect; unknown codes 404.
    public function test_t11_legacy_links_redirect(): void
    {
        $guestA = $this->guest($this->a, 'Guest A');

        $this->get('/?guest='.$guestA->guest_code)
            ->assertRedirect(url('/'.$this->a->slug).'?guest='.$guestA->guest_code);

        $this->get('/photobooth?guest='.$guestA->guest_code)
            ->assertRedirect(url('/'.$this->a->slug.'/photobooth').'?guest='.$guestA->guest_code);

        $this->get('/?guest=tidak-ada')->assertNotFound();
        $this->get('/photobooth?guest=tidak-ada')->assertNotFound();

        $this->get('/v/'.$this->a->slug)->assertStatus(301);
    }

    // T12 - reserved slugs are rejected on create.
    public function test_t12_reserved_slug_is_rejected(): void
    {
        $this->asAdminIn($this->a)
            ->post('/admin/invitations', [
                'title' => 'Reserved',
                'slug' => 'admin',
                'theme_id' => $this->a->theme_id,
                'status' => 'draft',
                'payment_status' => 'unpaid',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseMissing('invitations', ['slug' => 'admin']);
    }

    // T13 - drafts are hidden from visitors, visible to an admin.
    public function test_t13_draft_is_hidden_from_visitors_but_visible_to_admin(): void
    {
        $draft = Invitation::factory()->draft()->create();

        $this->get('/'.$draft->slug)->assertNotFound();

        $this->actingAs($this->admin)
            ->get('/'.$draft->slug)
            ->assertOk();
    }

    // T14 - archived invitations are gone for everyone.
    public function test_t14_archived_invitation_returns_404(): void
    {
        $archived = Invitation::factory()->archived()->create();

        $this->get('/'.$archived->slug)->assertNotFound();

        $this->actingAs($this->admin)
            ->get('/'.$archived->slug)
            ->assertNotFound();
    }
}
