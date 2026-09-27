<?php

namespace App\Support;

use App\Models\Enrollment;

/**
 * Certificate of completion: who has earned one, and how it is named.
 *
 * THE BAR is every core module's checkpoint approved. That is a claim worth making,
 * because ship-to-unlock means a human reviewed build proof for each one - this is not
 * a certificate for watching videos.
 *
 * It is deliberately NOT the completion guarantee (`guarantee_min_live_sessions`), which
 * also wants 4 of 6 live sessions attended. That threshold protects a refund promise.
 * Withholding a certificate from someone who shipped all nine workflows but missed live
 * calls would be arbitrary, and would have you arguing about attendance for a cohort.
 *
 * Cohort 1 is excluded: it predates ship-to-unlock, so it has no checkpoints to pass and
 * there is nothing to attest.
 *
 * NAMING. `certificate_name` is student-declared, so verification attests the
 * COMPLETION, not the identity - the same as most course credentials. Admin can see and
 * see it on /admin/progress. It is validated to a conservative character set so the
 * printed line stays a name and not a self-awarded job title.
 */
class Certificate
{
    /** Characters allowed in a printed name: letters (any script), space, hyphen, apostrophe, period. */
    public const NAME_RULE = ['nullable', 'string', 'min:2', 'max:60', 'regex:/^[\pL\pM\s\.\'\-]+$/u'];

    /** Has this student passed every core checkpoint? */
    public static function isEarnedBy(?Enrollment $enrollment): bool
    {
        if (! $enrollment || ! $enrollment->usesShipToUnlock()) {
            return false;
        }

        $core = Progress::coreModuleIds();

        if ($core === []) {
            return false;
        }

        $approved = $enrollment->approvedModuleIds();

        return count(array_intersect($core, $approved)) === count($core);
    }

    /**
     * Issue if earned, and return the enrollment.
     *
     * Idempotent: an already-issued certificate keeps its code and its original
     * issue date. Re-issuing would break every copy of the old verification link.
     */
    public static function issueFor(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->certificate_issued_at || ! self::isEarnedBy($enrollment)) {
            return $enrollment;
        }

        $enrollment->update([
            'certificate_code' => self::mintCode(),
            'certificate_issued_at' => now(),
        ]);

        return $enrollment->fresh();
    }

    /** The name to print: what they chose, else the name they enrolled with. */
    public static function nameFor(Enrollment $enrollment): string
    {
        $chosen = trim((string) $enrollment->certificate_name);

        return $chosen !== '' ? $chosen : trim((string) $enrollment->full_name);
    }

    /**
     * Resolve a public verification code to its enrollment, or null.
     *
     * Only an ISSUED certificate resolves, so a code that somehow exists without a
     * timestamp verifies as nothing rather than as a valid credential.
     */
    public static function verify(string $code): ?Enrollment
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        return Enrollment::where('certificate_code', $code)
            ->whereNotNull('certificate_issued_at')
            ->where('status', 'paid')
            ->first();
    }

    /**
     * The module titles a certificate attests, in curriculum order - printed on the
     * certificate so it says what was actually built, not just that something was.
     *
     * @return array<int, string>
     */
    public static function moduleTitles(): array
    {
        return collect(config('curriculum.core', []))
            ->pluck('title')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * A short, unambiguous code. No vowels, so it cannot spell anything, and none of
     * 0/O/1/I/L, which are the pairs people mistype when reading a code off a
     * screenshot on a phone - which is exactly how this will be shared.
     */
    private static function mintCode(): string
    {
        $alphabet = '23456789BCDFGHJKMNPQRSTVWXYZ';

        do {
            $code = 'AJ-';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (Enrollment::where('certificate_code', $code)->exists());

        return $code;
    }
}
