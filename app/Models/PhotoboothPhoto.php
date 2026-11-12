<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInvitation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoboothPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\PhotoboothPhotoFactory> */
    use BelongsToInvitation, HasFactory;

    protected $fillable = ['invitation_id', 'guest_id', 'image_path', 'message', 'voice_note_path'];

    public function guest(): BelongsTo
    {
        // A photo may legitimately lose its guest row but must never leak
        // across tenants, so the guest lookup also goes through the scope.
        return $this->belongsTo(Guest::class);
    }
}
