<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInvitation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    /** @use HasFactory<\Database\Factories\GalleryFactory> */
    use BelongsToInvitation, HasFactory;

    protected $fillable = ['invitation_id', 'filename', 'path', 'order'];
}
