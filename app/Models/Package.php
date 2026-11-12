<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'features',
        'limits',
        'is_public',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
        'price' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function invitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Feature flags keyed by config/features.php. Missing keys default to
     * false so a package can only enable what it explicitly declares.
     *
     * @return array<string, bool>
     */
    public function featureFlags(): array
    {
        $stored = is_array($this->features) ? $this->features : [];
        $flags = [];

        foreach (array_keys(config('features', [])) as $key) {
            $flags[$key] = (bool) ($stored[$key] ?? false);
        }

        return $flags;
    }

    /**
     * @return array<string, int|null>
     */
    public function limitValues(): array
    {
        $stored = is_array($this->limits) ? $this->limits : [];
        $values = [];

        foreach (\App\Http\Controllers\Admin\PackageController::LIMIT_KEYS as $key) {
            $values[$key] = isset($stored[$key]) && $stored[$key] !== null
                ? (int) $stored[$key]
                : null;
        }

        return $values;
    }
}
