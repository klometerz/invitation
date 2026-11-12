<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Theme>
 */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    public function definition(): array
    {
        $key = 'theme-'.Str::lower(Str::random(6));

        return [
            'key' => $key,
            'name' => 'Theme '.Str::upper(Str::random(4)),
            'slug' => $key,
            'preview_image' => null,
            'svelte_path' => 'Wedding',
            'is_premium' => false,
            'description' => null,
            'is_active' => true,
        ];
    }
}
