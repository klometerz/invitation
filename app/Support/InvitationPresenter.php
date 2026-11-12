<?php

namespace App\Support;

use App\Models\Invitation;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for values derived from an invitation
 * (AGENTS.md 5.7).
 *
 * Nothing downstream may hardcode a couple name, a date or a logo: views,
 * photobooth frames, print pages, SEO tags and export filenames all read from
 * here.
 */
class InvitationPresenter
{
    public function __construct(private Invitation $invitation) {}

    public static function for(Invitation $invitation): self
    {
        return new self($invitation);
    }

    /**
     * Array handed to Inertia as the `meta` prop and used by Blade for SEO.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'coupleNames' => $this->coupleNames(),
            'shortTitle' => $this->shortTitle(),
            'dateText' => $this->dateText(),
            'eventDate' => $this->eventDate()?->toDateString(),
            'coupleLabel' => $this->coupleLabel(),
            'logoUrl' => $this->logoUrl(),
            'loadingTips' => $this->loadingTips(),
            'thanks' => $this->thanks(),
            'photoboothUrl' => $this->photoboothUrl(),
            'inviteMessageTemplate' => $this->inviteMessageTemplate(),
            'seo' => $this->seo(),
        ];
    }

    public function content(): array
    {
        return is_array($this->invitation->content) ? $this->invitation->content : [];
    }

    /**
     * Woman first, as the existing design has always done.
     */
    public function coupleNames(): string
    {
        $wanita = $this->content()['mempelai']['wanita']['nama_panggilan'] ?? null;
        $pria = $this->content()['mempelai']['pria']['nama_panggilan'] ?? null;

        $parts = array_values(array_filter([
            is_string($wanita) ? trim($wanita) : null,
            is_string($pria) ? trim($pria) : null,
        ]));

        return implode(' & ', $parts);
    }

    public function shortTitle(): string
    {
        $names = $this->coupleNames();

        return $names !== '' ? 'The Wedding of '.$names : (string) $this->invitation->title;
    }

    /**
     * First event date, formatted the way the invitation shows it:
     * "06 . 09 . 2026".
     */
    public function dateText(): string
    {
        $date = $this->eventDate();

        if ($date === null) {
            $formatted = $this->content()['hero']['date_formatted'] ?? null;

            return is_string($formatted) ? $formatted : '';
        }

        return $date->format('d . m . Y');
    }

    public function eventDate(): ?Carbon
    {
        // AGENTS.md 5.7: the first `acara.tanggal` is authoritative; the
        // `event_date` column is the fallback (it was backfilled from it).
        $raw = $this->content()['acara'][0]['tanggal'] ?? null;

        if (is_string($raw) && trim($raw) !== '') {
            try {
                return Carbon::parse($raw);
            } catch (\Throwable) {
                // fall through to the column
            }
        }

        if ($this->invitation->event_date !== null) {
            return Carbon::parse($this->invitation->event_date);
        }

        return null;
    }

    /**
     * Compact label for filenames ("maul-kahfi") - safe for the filesystem.
     */
    public function coupleLabel(): string
    {
        return (string) $this->invitation->slug;
    }

    public function logoUrl(): ?string
    {
        $logo = $this->content()['logo_url'] ?? null;

        return is_string($logo) && $logo !== '' ? $logo : null;
    }

    /**
     * @return list<string>
     */
    public function loadingTips(): array
    {
        $tips = $this->content()['loading_tips'] ?? [];

        return is_array($tips)
            ? array_values(array_filter($tips, fn ($tip) => is_string($tip) && trim($tip) !== ''))
            : [];
    }

    /**
     * Bank accounts / QRIS for the thanks section. Never invented: an empty
     * structure means the section hides that block.
     *
     * @return array{bank_accounts: list<array{bank: string, number: string, holder: string}>, qris_image_url: ?string}
     */
    public function thanks(): array
    {
        $thanks = $this->content()['thanks'] ?? [];
        $accounts = $thanks['bank_accounts'] ?? [];

        $normalized = [];

        if (is_array($accounts)) {
            foreach ($accounts as $account) {
                if (! is_array($account)) {
                    continue;
                }

                $bank = trim((string) ($account['bank'] ?? ''));
                $number = trim((string) ($account['number'] ?? ''));
                $holder = trim((string) ($account['holder'] ?? ''));

                if ($bank === '' && $number === '') {
                    continue;
                }

                $normalized[] = ['bank' => $bank, 'number' => $number, 'holder' => $holder];
            }
        }

        $qris = $thanks['qris_image_url'] ?? null;

        return [
            'bank_accounts' => $normalized,
            'qris_image_url' => is_string($qris) && $qris !== '' ? $qris : null,
        ];
    }

    public function photoboothUrl(): ?string
    {
        if (! $this->invitation->hasFeature('photobooth')) {
            return null;
        }

        return url('/'.$this->invitation->slug.'/photobooth');
    }

    /**
     * Placeholders: {name} {link} {quota} {couple}.
     */
    public function inviteMessageTemplate(): string
    {
        $template = $this->content()['invite_message_template'] ?? null;

        if (is_string($template) && trim($template) !== '') {
            return $template;
        }

        return self::defaultInviteMessageTemplate();
    }

    public static function defaultInviteMessageTemplate(): string
    {
        return implode("\n", [
            'Kepada Yth.',
            'Bapak/Ibu/Saudara/i',
            '{name}',
            '',
            'Assalamualaikum Wr. Wb',
            'Dengan senang hati kami mengundang Anda untuk hadir pada acara pernikahan kami.',
            '',
            'Untuk info lengkap acara, bisa kunjungi tautan berikut:',
            '{link}',
            '',
            'Merupakan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir.',
            'Terima kasih atas doa dan restunya.',
            '',
            'Wassalamualaikum Wr. Wb',
            '',
            '*{couple}*',
        ]);
    }

    /**
     * Replace the placeholders in an invite message.
     */
    public function renderInviteMessage(string $template, string $name, string $link, ?int $quota = null): string
    {
        return str_replace(
            ['{name}', '{link}', '{quota}', '{couple}'],
            [$name, $link, (string) ($quota ?? 1), $this->coupleNames()],
            $template
        );
    }

    /**
     * @return array{title: string, description: string, image: ?string, url: string}
     */
    public function seo(): array
    {
        $description = $this->content()['hero']['title'] ?? null;

        if (! is_string($description) || trim($description) === '') {
            $description = 'Anda diundang untuk hadir di acara pernikahan kami.';
        }

        $title = $this->shortTitle();
        $couple = $this->coupleNames();

        if ($couple !== '' && $title !== '') {
            $title = $this->invitation->title.' - '.$couple;
        }

        return [
            'title' => $title !== '' ? $title : 'Undangan Pernikahan',
            'description' => $description,
            'image' => $this->ogImage(),
            'url' => url('/'.$this->invitation->slug),
        ];
    }

    /**
     * hero background -> first gallery image -> platform logo (may be null).
     */
    public function ogImage(): ?string
    {
        $hero = $this->content()['hero']['background_url'] ?? null;

        if (is_string($hero) && $hero !== '') {
            return $this->absolute($hero);
        }

        // Scoped to THIS invitation explicitly: the global tenant scope fails
        // closed, and the presenter may be used outside a request (commands,
        // tests) where no context has been set.
        $gallery = app(TenantContext::class)->runAs(
            $this->invitation->id,
            fn () => $this->invitation->galleries()->orderBy('order')->first()
        );

        if ($gallery !== null && is_string($gallery->path) && $gallery->path !== '') {
            return $this->absolute($gallery->path);
        }

        $platformLogo = Settings::get('platform_logo');

        return is_string($platformLogo) && $platformLogo !== '' ? $this->absolute($platformLogo) : null;
    }

    private function absolute(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
