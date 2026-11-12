<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for admin pages that crashed in the browser while the
 * server payload looked fine (P3 gate fix).
 *
 * These tests assert the exact prop SHAPES the Svelte pages destructure. A
 * page reading `users.data` when the server sends an array (or nothing) fails
 * silently in PHPUnit but throws in the browser, so the shape is pinned here.
 */
class AdminPagePayloadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function asAdmin(): self
    {
        $invitation = Invitation::factory()->create();

        return $this->actingAs($this->admin())
            ->withSession(['admin_invitation_id' => $invitation->id]);
    }

    public function test_users_page_sends_a_paginator_with_a_data_array(): void
    {
        User::factory()->create([
            'role' => User::ROLE_STAFF,
            'invitation_id' => Invitation::factory()->create()->id,
        ]);

        $this->asAdmin()
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(function ($page) {
                $props = $page->toArray()['props'];

                $this->assertArrayHasKey('users', $props, 'users prop is missing');
                $this->assertIsArray($props['users'], 'users must be a paginator object');
                $this->assertArrayHasKey('data', $props['users'], 'users.data is missing');
                $this->assertIsArray($props['users']['data']);

                // Every row carries the fields the page binds.
                foreach ($props['users']['data'] as $row) {
                    foreach (['id', 'name', 'email', 'role', 'is_active'] as $key) {
                        $this->assertArrayHasKey($key, $row);
                    }
                }

                $this->assertIsArray($props['invitations'], 'invitations must be an array');
            });
    }

    public function test_users_page_works_with_no_staff_accounts(): void
    {
        // Only the owner exists. Note: Invitation::factory() creates its own
        // user (default role `staff`), so reuse the admin for user_id.
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $invitation = Invitation::factory()->create(['user_id' => $admin->id]);

        $this->assertSame(
            0,
            User::where('role', '!=', User::ROLE_ADMIN)->count(),
            'fixture must contain no staff accounts'
        );

        $this->actingAs($admin)
            ->withSession(['admin_invitation_id' => $invitation->id])
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('users.data', 0)
            );
    }

    public function test_settings_page_sends_an_object_with_every_key(): void
    {
        $this->asAdmin()
            ->get('/admin/settings')
            ->assertOk()
            ->assertInertia(function ($page) {
                $settings = $page->toArray()['props']['settings'] ?? null;

                $this->assertIsArray($settings, 'settings must be an object');
                foreach ([
                    'business_name',
                    'whatsapp_number',
                    'instagram_url',
                    'footer_text',
                    'platform_logo',
                ] as $key) {
                    $this->assertArrayHasKey($key, $settings);
                }
            });
    }

    public function test_packages_page_sends_arrays(): void
    {
        Package::create([
            'name' => 'Basic', 'slug' => 'basic-payload',
            'price' => null, 'features' => [], 'limits' => [],
        ]);

        $this->asAdmin()
            ->get('/admin/packages')
            ->assertOk()
            ->assertInertia(function ($page) {
                $props = $page->toArray()['props'];

                $this->assertIsArray($props['packages']);
                $this->assertNotEmpty($props['packages']);
                $this->assertIsArray($props['featureRegistry'], 'featureRegistry must be an array');
                $this->assertIsArray($props['limitKeys'], 'limitKeys must be an array');

                $package = $props['packages'][0];
                foreach (['id', 'name', 'slug', 'features', 'limits', 'is_public', 'is_active'] as $key) {
                    $this->assertArrayHasKey($key, $package);
                }
                $this->assertIsArray($package['features']);
                $this->assertIsArray($package['limits']);
            });
    }

    public function test_dashboard_page_renders(): void
    {
        $this->asAdmin()
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->has('counts')
            );
    }
}
