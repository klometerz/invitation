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
}
