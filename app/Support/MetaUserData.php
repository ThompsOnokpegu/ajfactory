<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\MasterclassRegistration;
use Illuminate\Http\Request;

/**
 * Normalises and hashes people the way Meta expects them, in ONE place.
 *
 * Used by both the Conversions API (MetaConversions) and the customer-list audience
 * sync (MetaAudiences). Meta matches on SHA-256 hashes of normalised values, so a
 * stray capital letter or a "+" in a phone number is a silent mismatch - every rule
 * here comes from Meta's "customer information parameters" spec:
 *
 *   em      trim, lowercase
 *   ph      digits only, WITH country code, no leading zeros or "+"
 *   fn/ln   lowercase letters only (UTF-8 kept, punctuation dropped)
 *   country lowercase ISO 3166-1 alpha-2
 *
 * A "person" is the plain, un-hashed shape:
 *   ['email' => ?, 'phone' => ?, 'first_name' => ?, 'last_name' => ?, 'country' => ?]
 */
final class MetaUserData
{
    /** Column order of every row sent to a customer-list audience. */
    public const AUDIENCE_SCHEMA = ['EMAIL', 'PHONE', 'FN', 'LN', 'COUNTRY'];

    /** The currency someone paid in is the best country signal we have. USD is ambiguous. */
    private const CURRENCY_COUNTRY = ['NGN' => 'ng', 'GHS' => 'gh', 'KES' => 'ke', 'ZAR' => 'za'];

    /** Fallback: the dialling code of a normalised phone number. Longest prefix wins. */
    private const DIAL_COUNTRY = ['234' => 'ng', '233' => 'gh', '254' => 'ke', '27' => 'za', '44' => 'gb', '1' => 'us'];

    public static function hash(?string $normalised): ?string
    {
        return ($normalised === null || $normalised === '') ? null : hash('sha256', $normalised);
    }

    public static function email(?string $raw): ?string
    {
        $email = strtolower(trim((string) $raw));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Port of the n8n normaliser (docs/n8n/aj-buildai-waitlist-masterclass.json):
     * Nigeria-first, but leaves already-international numbers alone.
     *
     *   08012345678        -> 2348012345678   (local format: drop the 0, add the default code)
     *   +234 801 234 5678  -> 2348012345678
     *   002348012345678    -> 2348012345678   (00 = international dialling prefix)
     *   2348012345678      -> 2348012345678   (assumed international already)
     */
    public static function phone(?string $raw, string $defaultCc = '234'): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (! $hasPlus) {
            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            } elseif (str_starts_with($digits, '0')) {
                $digits = $defaultCc.substr($digits, 1);
            }
        }

        $len = strlen($digits);

        return ($len >= 8 && $len <= 15) ? $digits : null;
    }

    /** Lowercase letters only. "Mary-Jane O'Neil" -> "maryjaneoneil". */
    public static function name(?string $raw): ?string
    {
        $name = preg_replace('/[^\p{L}]+/u', '', mb_strtolower(trim((string) $raw))) ?? '';

        return $name === '' ? null : $name;
    }

    /**
     * Split a single full_name into [first, last]. First token is the first name,
     * everything after it is the last name; a lone token has no last name.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function splitName(?string $full): array
    {
        $parts = preg_split('/\s+/', trim((string) $full), 2) ?: [];

        return [self::name($parts[0] ?? null), self::name($parts[1] ?? null)];
    }

    /** Currency first (it is what they actually paid in), then the phone's dialling code. */
    public static function country(?string $currency, ?string $phoneDigits): ?string
    {
        $fromCurrency = self::CURRENCY_COUNTRY[strtoupper((string) $currency)] ?? null;
        if ($fromCurrency) {
            return $fromCurrency;
        }

        if ($phoneDigits) {
            foreach ([3, 2, 1] as $len) {
                $prefix = substr($phoneDigits, 0, $len);
                if (isset(self::DIAL_COUNTRY[$prefix])) {
                    return self::DIAL_COUNTRY[$prefix];
                }
            }
        }

        return null;
    }

    /** @return array{email: ?string, phone: ?string, first_name: ?string, last_name: ?string, country: ?string} */
    public static function fromEnrollment(Enrollment $e): array
    {
        [$first, $last] = self::splitName($e->full_name);
        $phone = self::phone($e->whatsapp);

        return [
            'email' => self::email($e->email),
            'phone' => $phone,
            'first_name' => $first,
            'last_name' => $last,
            'country' => self::country($e->currency, $phone),
        ];
    }

    /** @return array{email: ?string, phone: ?string, first_name: ?string, last_name: ?string, country: ?string} */
    public static function fromRegistration(MasterclassRegistration $r): array
    {
        $phone = self::phone($r->whatsapp);

        return [
            'email' => self::email($r->email),
            'phone' => $phone,
            'first_name' => self::name($r->first_name),
            'last_name' => self::name($r->last_name),
            'country' => self::country(null, $phone),
        ];
    }

    /**
     * The browser signals Meta uses to match a server event to the person who was
     * actually on the page. Captured at checkout and stored on the enrollment, because
     * the webhook that fires the Purchase later comes from the gateway, not the buyer.
     *
     * _fbp/_fbc only survive to here because bootstrap/app.php exempts them from
     * cookie encryption.
     *
     * @return array{fbp: ?string, fbc: ?string, ip: ?string, ua: ?string, captured_at: string}
     */
    public static function contextFromRequest(Request $request): array
    {
        // Behind a CDN/proxy the framework sees the proxy's address; prefer the
        // forwarded header when one is present.
        $ip = $request->header('CF-Connecting-IP')
            ?: trim(explode(',', (string) $request->header('X-Forwarded-For'))[0])
            ?: $request->ip();

        return [
            'fbp' => $request->cookie('_fbp') ?: null,
            'fbc' => $request->cookie('_fbc') ?: null,
            'ip' => $ip ?: null,
            'ua' => $request->userAgent() ?: null,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Conversions API `user_data`: identity fields hashed, browser signals as-is.
     * Keys with no value are omitted rather than sent empty.
     *
     * @param  array{email?: ?string, phone?: ?string, first_name?: ?string, last_name?: ?string, country?: ?string}  $person
     * @param  array{fbp?: ?string, fbc?: ?string, ip?: ?string, ua?: ?string}  $context
     */
    public static function capiUserData(array $person, array $context = [], ?string $externalId = null): array
    {
        $hashed = [
            'em' => self::hash($person['email'] ?? null),
            'ph' => self::hash($person['phone'] ?? null),
            'fn' => self::hash($person['first_name'] ?? null),
            'ln' => self::hash($person['last_name'] ?? null),
            'country' => self::hash($person['country'] ?? null),
            'external_id' => self::hash($externalId),
        ];

        $plain = [
            'client_ip_address' => $context['ip'] ?? null,
            'client_user_agent' => $context['ua'] ?? null,
            'fbp' => $context['fbp'] ?? null,
            'fbc' => $context['fbc'] ?? null,
        ];

        return array_filter($hashed + $plain, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * One customer-list row in AUDIENCE_SCHEMA order. Meta accepts partial rows, so a
     * missing key is sent as an empty string rather than dropping the whole person.
     *
     * @return array<int, string>
     */
    public static function audienceRow(array $person): array
    {
        return [
            self::hash($person['email'] ?? null) ?? '',
            self::hash($person['phone'] ?? null) ?? '',
            self::hash($person['first_name'] ?? null) ?? '',
            self::hash($person['last_name'] ?? null) ?? '',
            self::hash($person['country'] ?? null) ?? '',
        ];
    }
}
