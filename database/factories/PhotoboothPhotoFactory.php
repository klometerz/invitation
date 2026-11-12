<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PhotoboothPhoto>
 *
 * `invitation_id` must be supplied by the caller or an active TenantContext.
 */
class PhotoboothPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'image_path' => '/storage/photobooth/'.Str::random(20).'.jpg',
            'message' => fake()->sentence(),
        ];
    }
}
