<?php

namespace App\Support;

/**
 * Ordered invitation sections and the package feature each one needs
 * (AGENTS.md 5.5).
 *
 * `welcome` is always on. A section whose feature is disabled by the package
 * is removed server-side, so a theme can never render a paid block that the
 * client did not buy.
 */
class SectionRegistry
{
    /**
     * key => required feature key (null = always available).
     *
     * @var array<string, string|null>
     */
    public const SECTIONS = [
        'welcome' => null,
        'verses' => null,
        'event' => null,
        'info' => 'info_section',
        'gallery' => 'gallery',
        'rsvp' => 'rsvp',
        'thanks' => null,
    ];

    /** @var list<string> */
    public const DEFAULT_ORDER = [
        'welcome',
        'verses',
        'event',
        'info',
        'gallery',
        'rsvp',
        'thanks',
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::SECTIONS);
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::SECTIONS);
    }

    public static function requiredFeature(string $key): ?string
    {
        return self::SECTIONS[$key] ?? null;
    }

    public static function label(string $key): string
    {
        return match ($key) {
            'welcome' => 'Pembuka / mempelai',
            'verses' => 'Ayat & ucapan syukur',
            'event' => 'Acara',
            'info' => 'Info & pengumuman',
            'gallery' => 'Galeri',
            'rsvp' => 'RSVP',
            'thanks' => 'Terima kasih',
            default => $key,
        };
    }
}
