<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'slug',
        'preview_image',
        'svelte_path',
        'is_premium',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relasi: Satu tema bisa dipakai oleh banyak undangan
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
