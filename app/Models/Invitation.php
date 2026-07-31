<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    protected $fillable = [
        'user_id',
        'theme_id',
        'slug',
        'title',
        'content',
        'is_active',
    ];

    // Penting: Agar content otomatis jadi array saat diakses di Controller/Svelte
    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    // Relasi ke tabel tamu yang sudah lo buat
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }
}