<?php

namespace App\Support;

/**
 * Matching a Resource against the gated/purchasable guide lists.
 *
 * The lists in config/guides.php are PATHS, but a Resource's `url` is free text
 * typed into Admin -> Resources. An exact string comparison therefore fails on an
 * absolute address or a stray trailing slash, and it fails SILENTLY: the access
 * page stops appending the unlock token, so a paying buyer lands on the sales page
 * holding a receipt, and the locked page offers no Buy button because nothing looks
 * sellable either. That is exactly what happened to the first guide sale.
 *
 * So compare paths, not strings.
 */
class Guides
{
    /** Reduce whatever was typed to a comparable path, or null if it is not ours. */
    public static function normalisePath(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        // An off-site link is never one of our guides, even if its path collides.
        $host = parse_url($url, PHP_URL_HOST);
        if ($host && ! in_array($host, self::ownHosts(), true)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $path = '/'.trim($path, '/');

        return $path === '/' ? null : $path;
    }

    public static function isGated(?string $url): bool
    {
        return in_array(self::normalisePath($url), config('guides.gated_paths', []), true);
    }

    public static function isPurchasable(?string $url): bool
    {
        return in_array(self::normalisePath($url), config('guides.purchasable_paths', []), true);
    }

    /** The link to hand a buyer: always same-site, with their unlock token. */
    public static function unlockUrl(?string $url, string $token): ?string
    {
        $path = self::normalisePath($url);

        return $path ? $path.'?t='.$token : null;
    }

    /** @return array<int, string> */
    private static function ownHosts(): array
    {
        return collect([config('app.url'), config('app.public_url')])
            ->map(fn ($u) => parse_url((string) $u, PHP_URL_HOST))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
