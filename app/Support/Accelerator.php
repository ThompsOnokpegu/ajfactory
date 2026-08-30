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

    /**
     * The cohort currently being sold. Stamped on new enrollments and used
     * everywhere the number appears in copy — never hardcode it in a Blade file,
     * or the next launch ships a page that still says the old number.
     */
    public static function cohortNumber(): int
    {
        return (int) config('accelerator.cohort_number', 1);
    }

    /** "Cohort 3" — plain prose form. */
    public static function cohortLabel(): string
    {
        return 'Cohort '.self::cohortNumber();
    }

    /** "Cohort 03" — zero-padded form used in the big display headings. */
    public static function cohortLabelPadded(): string
    {
        return 'Cohort '.str_pad((string) self::cohortNumber(), 2, '0', STR_PAD_LEFT);
    }

    /** Confirmed (paid) enrolments in the CURRENT cohort — drives seats/early-bird. */
    public static function seatsSold(): int
    {
        return (int) Enrollment::where('status', 'paid')
            ->where('cohort', self::cohortNumber())
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

    /** The whole currency table, as configured. */
    public static function currencies(): array
    {
        return (array) config('accelerator.currencies', []);
    }

    /**
     * Currencies the checkout may actually offer: a provider, plus every price set.
     *
     * A currency missing a price is filtered out rather than defaulted, so a
     * half-configured currency can never be sold at the wrong price - it simply does
     * not appear until someone fills the numbers in.
     *
     * @return array<int, string>
     */
    public static function enabledCurrencies(): array
    {
        return collect(self::currencies())
            ->filter(function (array $c, string $code) {
                if (empty($c['provider'])) {
                    return false;
                }

                // Naira prices live at the top level, not in the table.
                if ($code === 'NGN') {
                    return true;
                }

                foreach (['price_full', 'price_earlybird', 'installment_each'] as $key) {
                    if (! is_numeric($c[$key] ?? null)) {
                        return false;
                    }
                }

                return true;
            })
            ->keys()
            ->all();
    }

    public static function isSupportedCurrency(?string $currency): bool
    {
        return in_array((string) $currency, self::enabledCurrencies(), true);
    }

    /** Falls back to the base currency, so a bad value can never reach a charge. */
    public static function safeCurrency(?string $currency): string
    {
        return self::isSupportedCurrency($currency)
            ? (string) $currency
            : (string) config('accelerator.currency', 'NGN');
    }

    public static function currencySymbol(string $currency = 'NGN'): string
    {
        return (string) (config("accelerator.currencies.{$currency}.symbol") ?: $currency.' ');
    }

    /** Which gateway collects this currency. */
    public static function paymentProvider(string $currency = 'NGN'): string
    {
        return (string) (config("accelerator.currencies.{$currency}.provider") ?: 'paystack');
    }

    /**
     * One price lookup for every currency.
     *
     * NGN reads the top-level keys - the single source of truth the marketing copy
     * also reads - and every other currency reads its own row in the table.
     */
    private static function price(string $currency, string $key): float
    {
        if ($currency === 'NGN') {
            return (float) config("accelerator.{$key}");
        }

        return (float) config("accelerator.currencies.{$currency}.{$key}");
    }

    /** Price for the pay-in-full plan - early-bird price when active, else full. */
    public static function fullPrice(string $currency = 'NGN'): float
    {
        return self::price($currency, self::earlybirdActive() ? 'price_earlybird' : 'price_full');
    }

    /** Sticker (non early-bird) full price - used to show the strike-through anchor. */
    public static function regularFullPrice(string $currency = 'NGN'): float
    {
        return self::price($currency, 'price_full');
    }

    /** Amount charged per installment payment. */
    public static function installmentEach(string $currency = 'NGN'): float
    {
        return self::price($currency, 'installment_each');
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

    /**
     * Has the cohort already begun? Selling copy differs either side of this:
     * before the start it's "starts Saturday 12th September", after it it's
     * "already running, but it's self-paced so you can still catch up". Getting
     * this wrong is a visible lie on the landing page, so it's derived, not typed.
     */
    public static function hasStarted(): bool
    {
        $value = config('accelerator.cohort_starts_at');

        if (! $value) {
            return false;
        }

        return Carbon::now('Africa/Lagos')->gte(Carbon::parse($value, 'Africa/Lagos'));
    }

    /**
     * The date floor that gates module 01 for a student in $cohort, or null for no floor.
     *
     * The floor exists to stop someone who paid early from starting before day one. It is
     * therefore only meaningful for the cohort currently being sold: a student from an
     * EARLIER cohort is by definition already past their own start date.
     *
     * Reading `cohort_starts_at` for everyone is a live incident, not a hypothetical.
     * Scheduling Cohort 3 for 12 Sep moved the global start into the future and instantly
     * re-locked module 01 for every Cohort 2 student mid-course — they had shipped
     * checkpoints and were simply shut out. A start floor must never move forward
     * underneath someone who has already begun.
     */
    public static function startFloorFor(?int $cohort): ?Carbon
    {
        if ($cohort !== null && $cohort < self::cohortNumber()) {
            return null;
        }

        $value = config('accelerator.cohort_starts_at');

        return $value ? Carbon::parse($value, 'Africa/Lagos') : null;
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

        // flat: 'value' is the PRICE the student should end up paying, per currency -
        // not a discount. Immune to the base price moving underneath it, which a fixed
        // amount is not: early-bird ending or the 10th seat selling changes the base by
        // 10,000, and a fixed coupon would silently charge the wrong total mid-promo.
        if (($coupon['type'] ?? '') === 'flat') {
            $value = $coupon['value'] ?? [];
            $target = is_array($value) ? ($value[$currency] ?? null) : $value;

            // No price set for this currency: charge full rather than invent one.
            if ($target === null) {
                return 0.0;
            }

            return round(max(0.0, $amount - max(0.0, (float) $target)), 2);
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
     *
     * $cohort is the STUDENT'S cohort. Omit it at checkout (they're joining the cohort
     * being sold); pass it when recomputing an existing student, or the current cohort's
     * start becomes their anchor. `installments:realign` recomputes every outstanding
     * student regardless of cohort, so with Cohort 3 starting 12 Sep it would have handed
     * mid-course Cohort 2 students a deadline three weeks later than they'd earned and
     * un-suspended anyone already suspended for non-payment. Same root cause as the
     * module-01 lockout — see startFloorFor().
     */
    public static function installmentDueAt(?Carbon $paidAt = null, ?int $cohort = null): Carbon
    {
        $days = (int) config('accelerator.installment_due_days', 21);
        $paidAt = $paidAt ? $paidAt->copy() : Carbon::now();

        $cohortStart = self::startFloorFor($cohort);

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
