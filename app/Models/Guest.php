<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Guest extends Model {
    // TAMBAHIN is_rsvp dan attendance di sini
    protected $fillable = [
        'invitation_id', 'name', 'city', 'category', 'guest_code', 'quota',
        'is_rsvp', 'attendance', 'message',
        'checkin_status', 'checked_in_at', 'actual_guest_count', 'checked_in_by'
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    protected static function booted() {
        static::creating(function ($guest) {
            // Generate kode: mk-nama-random5char
            $guest->guest_code = Str::slug($guest->name) . '-' . Str::lower(Str::random(5));
        });
    }
    // Di dalam class Guest extends Model
public function invitation(): BelongsTo
{
    return $this->belongsTo(Invitation::class);
}
}