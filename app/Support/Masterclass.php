<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Derived state for the TAAB masterclass session.
 *
 * Reads config/taab.php so the hub page, the registration guard, and the
 * `masterclass:remind` scheduler all agree on when the session runs and
 * whether registration is still open.
 */
class Masterclass
{
    /** The masterclass config block. */
    public static function session(): array
    {
        return config('taab.masterclass', []);
    }

    /** Timezone the session datetimes are expressed in (app default is UTC). */
    public static function timezone(): string
    {
        return self::session()['timezone'] ?? config('app.timezone', 'UTC');
    }

    public static function startsAt(): ?Carbon
    {
        $value = self::session()['starts_at'] ?? null;

        return $value ? Carbon::parse($value, self::timezone()) : null;
    }

    public static function endsAt(): ?Carbon
    {
        $value = self::session()['ends_at'] ?? null;

        return $value ? Carbon::parse($value, self::timezone()) : null;
    }

    /**
     * Registration is open when a session is scheduled, the cut-off (end of the
     * registration_closes day) hasn't passed, and the session hasn't started.
     */
    public static function registrationOpen(): bool
    {
        $session = self::session();

        if (empty($session['date'])) {
            return false;
        }

        $now = now();

        $closes = ! empty($session['registration_closes'])
            ? Carbon::parse($session['registration_closes'], self::timezone())->endOfDay()
            : null;

        if ($closes && $now->greaterThan($closes)) {
            return false;
        }

        $startsAt = self::startsAt();
        if ($startsAt && $now->greaterThanOrEqualTo($startsAt)) {
            return false;
        }

        return true;
    }
}
