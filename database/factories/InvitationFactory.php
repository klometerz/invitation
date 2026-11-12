<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Both FKs are NOT NULL in the legacy schema.
            'user_id' => User::factory(),
            'theme_id' => Theme::factory(),
            'slug' => Str::lower(Str::random(8)).'-'.Str::lower(Str::random(4)),
            'title' => 'The Wedding of '.fake()->firstName().' & '.fake()->firstName(),
            'content' => [
                'hero' => ['title' => 'The Wedding', 'date_formatted' => '01 . 01 . 2027'],
                'mempelai' => [
                    'pria' => ['nama_lengkap' => 'Pria', 'nama_panggilan' => 'Pria'],
                    'wanita' => ['nama_lengkap' => 'Wanita', 'nama_panggilan' => 'Wanita'],
                ],
                'acara' => [],
            ],
            'is_active' => false,
            'status' => 'active',
            'package_id' => null,
            'event_date' => now()->addMonth()->toDateString(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => 'archived']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'active_until' => now()->subDay(),
        ]);
    }
}
