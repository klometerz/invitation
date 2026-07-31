<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Guest extends Model {
    // TAMBAHIN is_rsvp dan attendance di sini
    protected $fillable = ['invitation_id', 'name', 'city', 'guest_code', 'is_rsvp', 'attendance'];

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