<?php

namespace App\Support;

/**
 * Slug rules for the public URL segment (AGENTS.md 5.2).
 *
 * `pattern()` is used both by the route constraint and by the invitation
 * validation, so the two can never drift apart.
 */
class SlugRules
{
    /** Lowercase alphanumeric blocks separated by single hyphens. */
    public const PATTERN = '^[a-z0-9]+(-[a-z0-9]+)*$';

    /**
     * Paths that must never be taken by a client, because they either are
     * literal routes or would shadow platform files.
     *
     * @var list<string>
     */
    public const RESERVED = [
        'admin',
        'login',
        'logout',
        'register',
        'api',
        'photobooth',
        'storage',
        'build',
        'up',
        'v',
        // Reserved by the demo invitation (P3-T7) and by the static demo asset
        // folder. A physical directory under public/ also shadows a route, so
        // this must never be handed to a client.
        'demo',
        'demo-assets',
        'terms',
        'privacy',
        'pricing',
        'contact',
        'about',
        'assets',
        'vendor',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',
    ];

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::RESERVED, true);
    }

    /**
     * Laravel validation rules for an invitation slug.
     *
     * @return list<string>
     */
    public static function validationRules(?int $ignoreInvitationId = null): array
    {
        $unique = 'unique:invitations,slug';

        if ($ignoreInvitationId !== null) {
            $unique .= ','.$ignoreInvitationId;
        }

        return [
            'required',
            'string',
            'max:255',
            'regex:/'.self::PATTERN.'/',
            $unique,
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) && self::isReserved($value)) {
                    $fail('Slug "'.$value.'" tidak tersedia, silakan pilih yang lain.');
                }
            },
        ];
    }
}
