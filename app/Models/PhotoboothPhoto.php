<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoboothPhoto extends Model
{
    protected $fillable = ['guest_id', 'image_path', 'message', 'voice_note_path'];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}