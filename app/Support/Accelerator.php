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

    /**
     * Look up a valid coupon by code (case-insensitive), or null. Enforces expiry
     * here so the checkout never has to. Returns the config entry + its 'code'.
     *
     * @return array<string,mixed>|null
     */
    public static function coupon(?string $code): ?array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        $coupons = array_change_key_case(config('accelerator.coupons', []), CASE_UPPER);
        $c = $coupons[$code] ?? null;
        if (! $c) {
            return null;
        }

        if (! empty($c['expires_at']) && Carbon::now('Africa/Lagos')->greaterThan(Carbon::parse($c['expires_at'], 'Africa/Lagos'))) {
            return null;
        }

        return $c + ['code' => $code];
    }

    /** Whether a coupon applies to the given plan (defaults to all plans). */
    public static function couponAppliesToPlan(array $coupon, string $plan): bool
    {
        return in_array($plan, $coupon['plans'] ?? ['full', 'installment'], true);
    }

    /**
     * Naira/USD discount a coupon gives on a base amount. Never exceeds the amount
     * (no negative prices). Fixed coupons are per-currency; a currency with no
     * fixed value gets no discount.
     */
    public static function couponDiscount(array $coupon, float $amount, string $currency = 'NGN'): float
    {
        if (($coupon['type'] ?? '') === 'percent') {
            $pct = max(0.0, min(100.0, (float) ($coupon['value'] ?? 0)));

            return round($amount * $pct / 100, 2);
        }

        // fixed: value is a per-currency map
        $value = $coupon['value'] ?? [];
        $fixed = is_array($value) ? (float) ($value[$currency] ?? 0) : (float) $value;

        return min(max(0.0, $fixed), $amount);
    }

    /**
     * When an installment student's 2nd payment falls due.
     *
     * Anchored to the COHORT START, not the payment date. Counting from payment
     * punished early enrollment: someone who paid two weeks before the cohort
     * opened had to clear their balance before they had really started, while
     * someone who paid on day one got the full window. The anchor is therefore the
     * LATER of (cohort start, when they paid) — early birds get the full window
     * measured from the start line, and nobody who joins mid-cohort gets less than
     * the full window either.
     *
     * $paidAt defaults to now, which is the right anchor at checkout time.
     */
    public static function installmentDueAt(?Carbon $paidAt = null): Carbon
    {
        $days = (int) config('accelerator.installment_due_days', 21);
        $paidAt = $paidAt ? $paidAt->copy() : Carbon::now();

        $start = config('accelerator.cohort_starts_at');
        $cohortStart = $start ? Carbon::parse($start, 'Africa/Lagos') : null;

        $anchor = ($cohortStart && $cohortStart->greaterThan($paidAt)) ? $cohortStart : $paidAt;

        return $anchor->copy()->addDays($days);
    }

    /** Published testimonials only — never fabricated, empty by default. */
    public static function publishedTestimonials(): Collection
    {
        return collect(config('accelerator.testimonials', []))
            ->filter(fn ($t) => ! empty($t['is_published']))
            ->values();
    }
}
