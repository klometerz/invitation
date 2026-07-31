<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'preview_image',
        'svelte_path',
        'is_premium',
    ];

    // Relasi: Satu tema bisa dipakai oleh banyak undangan
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}