<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Derived state for the AI Automation Accelerator offer.
 *
 * Reads config/accelerator.php + the enrollments table so the landing page and
 * checkout always agree on seats left, early-bird status, and the live price.
 */
class Accelerator
{
    /** Settings key for the runtime registration ON/OFF switch. */
    public const REGISTRATION_FLAG = 'accelerator_registration_open';

    /** Is cohort registration accepting new sign-ups? Owner toggles this from the admin. */
    public static function registrationOpen(): bool
    {
        return Setting::flag(self::REGISTRATION_FLAG, true);
    }

    /** Pause/resume cohort registration. */
    public static function setRegistrationOpen(bool $open): void
    {
        Setting::put(self::REGISTRATION_FLAG, $open ? '1' : '0');
    }
    /** Confirmed (paid) enrolments in the CURRENT cohort — drives seats/early-bird. */
    public static function seatsSold(): int
    {
        return (int) Enrollment::where('status', 'paid')
            ->where('cohort', (int) config('accelerator.cohort_number', 2))
            ->count();
    }

    /** Seats remaining against the cohort cap (never negative). */
    public static function seatsLeft(): int
    {
        return max(0, (int) config('accelerator.cohort_cap') - self::seatsSold());
    }

    public static function isSoldOut(): bool
    {
        return self::seatsLeft() <= 0;
    }

    /**
     * Early-bird is active while we are under the early-bird seat count AND
     * before the deadline. A null deadline means "seat-gated only" so the
     * tier still works before the owner sets the 72h window.
     */
    public static function earlybirdActive(): bool
    {
        if (self::seatsSold() >= (int) config('accelerator.earlybird_seats')) {
            return false;
        }

        $endsAt = config('accelerator.earlybird_ends_at');

        if (empty($endsAt)) {
            return true;
        }

        return Carbon::now('Africa/Lagos')->lt(Carbon::parse($endsAt, 'Africa/Lagos'));
    }

    /**
     * Price for the pay-in-full plan — early-bird price when active, else full.
     *
     * @param  string  $currency  'NGN' | 'USD'
     */
    public static function fullPrice(string $currency = 'NGN'): float
    {
        if ($currency === 'USD') {
            return (float) (self::earlybirdActive()
                ? config('accelerator.usd.price_earlybird')
                : config('accelerator.usd.price_full'));
        }

        return (float) (self::earlybirdActive()
            ? config('accelerator.price_earlybird')
            : config('accelerator.price_full'));
    }

    /** Sticker (non early-bird) full price — used to show the strike-through anchor. */
    public static function regularFullPrice(string $currency = 'NGN'): float
    {
        return (float) ($currency === 'USD'
            ? config('accelerator.usd.price_full')
            : config('accelerator.price_full'));
    }

    /** Amount charged per installment payment. */
    public static function installmentEach(string $currency = 'NGN'): float
    {
        return (float) ($currency === 'USD'
            ? config('accelerator.usd.installment_each')
            : config('accelerator.installment_each'));
    }

    public static function installmentCount(): int
    {
        return (int) config('accelerator.installment_count');
    }

    /** Total cost of the installment plan (each × count). */
    public static function installmentTotal(string $currency = 'NGN'): float
    {
        return self::installmentEach($currency) * self::installmentCount();
    }

    public static function cohortStartsAt(): ?Carbon
    {
        $value = config('accelerator.cohort_starts_at');

        return $value ? Carbon::parse($value) : null;
    }

    /** When registration/enrolment closes (cart close). Drives the deadline copy. */
    public static function cartClosesAt(): ?Carbon
    {
        $value = config('accelerator.cart_closes_at');

        return $value ? Carbon::parse($value, 'Africa/Lagos') : null;
    }

    /** Published testimonials only — never fabricated, empty by default. */
    public static function publishedTestimonials(): Collection
    {
        return collect(config('accelerator.testimonials', []))
            ->filter(fn ($t) => ! empty($t['is_published']))
            ->values();
    }
}
