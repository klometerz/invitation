<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gallery>
 *
 * `invitation_id` must be supplied by the caller or an active TenantContext.
 */
class GalleryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'filename' => Str::random(10).'.jpg',
            'path' => '/storage/gallery/'.Str::random(16).'.jpg',
            'order' => 0,
        ];
    }
}
