<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInvitation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Guest extends Model
{
    /** @use HasFactory<\Database\Factories\GuestFactory> */
    use BelongsToInvitation, HasFactory;

    // TAMBAHIN is_rsvp dan attendance di sini
    protected $fillable = [
        'invitation_id', 'name', 'city', 'category', 'guest_code', 'quota',
        'is_rsvp', 'attendance', 'message',
        'checkin_status', 'checked_in_at', 'actual_guest_count', 'checked_in_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($guest) {
            // Generate kode: mk-nama-random5char
            $guest->guest_code = Str::slug($guest->name).'-'.Str::lower(Str::random(5));
        });
    }

    public function photoboothPhotos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PhotoboothPhoto::class);
    }
}
