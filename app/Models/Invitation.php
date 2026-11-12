<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme_id',
        'slug',
        'title',
        'content',
        'is_active',
        // Multi-tenant / lifecycle columns (P1-T1)
        'status',
        'package_id',
        'feature_overrides',
        'sections',
        'active_from',
        'active_until',
        'event_date',
        'client_name',
        'client_phone',
        'client_email',
        'price_agreed',
        'payment_status',
        'amount_paid',
        'paid_at',
        'internal_notes',
        'is_demo',
    ];

    // Penting: Agar content otomatis jadi array saat diakses di Controller/Svelte
    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
        'feature_overrides' => 'array',
        'sections' => 'array',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'event_date' => 'date',
        'paid_at' => 'datetime',
        'price_agreed' => 'integer',
        'amount_paid' => 'integer',
        'is_demo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    // Relasi ke tabel tamu yang sudah lo buat
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class);
    }

    public function photoboothPhotos(): HasMany
    {
        return $this->hasMany(PhotoboothPhoto::class);
    }

    /**
     * Feature keys known in v1 (P2-T1 will move this to config/features.php
     * and render the Packages GUI from it).
     */
    public const FEATURES = [
        'rsvp',
        'gallery',
        'music',
        'info_section',
        'guestbook',
        'qr_cards',
        'photobooth',
        'memory_book',
    ];

    /**
     * Effective feature flags: package defaults, overridden per invitation.
     *
     * An invitation without a package keeps every feature enabled so the
     * existing (pre-multi-tenant) wedding behaves exactly as before.
     *
     * @return array<string, bool>
     */
    public function featureFlags(): array
    {
        $flags = array_fill_keys(self::FEATURES, true);

        if ($this->package !== null) {
            $packageFeatures = is_array($this->package->features) ? $this->package->features : [];
            $flags = array_merge($flags, array_intersect_key($packageFeatures, $flags));
        }

        $overrides = is_array($this->feature_overrides) ? $this->feature_overrides : [];
        $flags = array_merge($flags, array_intersect_key($overrides, $flags));

        return array_map(fn ($value) => (bool) $value, $flags);
    }

    public function hasFeature(string $key): bool
    {
        return $this->featureFlags()[$key] ?? false;
    }

    /**
     * Effective limit for a key, or null when unlimited.
     * Overrides on the invitation win over the package value.
     */
    public function limit(string $key): ?int
    {
        $overrides = is_array($this->feature_overrides) ? $this->feature_overrides : [];
        $limits = $this->package !== null && is_array($this->package->limits) ? $this->package->limits : [];
        $limits = array_merge($limits, $overrides);

        $value = $limits[$key] ?? null;

        return $value === null ? null : (int) $value;
    }
}
