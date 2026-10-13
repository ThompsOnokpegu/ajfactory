<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Accelerator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only snapshot of the live offer, for Veta's consulting WhatsApp AI.
 *
 * Veta fills these values into its KB text when it answers, so price, cohort,
 * early-bird and masterclass facts are never retyped into its knowledge base —
 * that copy had already drifted (it still quoted the pre-21-Sep price).
 *
 * Every value is derived through App\Support\Accelerator, the same source the
 * landing page and checkout read, so the AI can never disagree with the site.
 * The map is flat ("accelerator.price_full.NGN") because Veta substitutes keys
 * straight into `{{ key }}` placeholders.
 *
 * Deliberately excluded: coupons (a code in a chat transcript is a leaked code),
 * community/Meet/WhatsApp-group links, and anything about enrolled students
 * beyond the testimonials they consented to publish on the landing page.
 */
class OfferController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $expected = (string) config('services.veta.feed_token');

        // No token configured means the feed is off, not open.
        if ($expected === '' || ! hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return response()->json($this->snapshot());
    }

    /**
     * @return array<string, string|int|bool|null>
     */
    private function snapshot(): array
    {
        $now = Carbon::now('Africa/Lagos');
        $cartClosesAt = Accelerator::cartClosesAt();

        $facts = [
            'accelerator.cohort_number'     => Accelerator::cohortNumber(),
            'accelerator.cohort_label'      => Accelerator::cohortLabel(),
            'accelerator.cohort_starts_at'  => Accelerator::cohortStartsAt()?->toDateString(),
            'accelerator.has_started'       => Accelerator::hasStarted(),
            'accelerator.registration_open' => Accelerator::registrationOpen(),
            'accelerator.sold_out'          => Accelerator::isSoldOut(),
            'accelerator.seats_left'        => Accelerator::seatsLeft(),
            'accelerator.cart_closes_at'    => $cartClosesAt?->toDateString(),
            // The one answer to "can I enrol today?" — the admin switch, seats, and
            // the cart deadline all have to agree, so derive it once here rather than
            // asking the AI to combine three flags correctly.
            'accelerator.enrolment_open'    => Accelerator::registrationOpen()
                && ! Accelerator::isSoldOut()
                && ($cartClosesAt === null || $now->lt($cartClosesAt)),
            'accelerator.earlybird_active'  => Accelerator::earlybirdActive(),
            'accelerator.earlybird_ends_at' => config('accelerator.earlybird_ends_at')
                ? Carbon::parse(config('accelerator.earlybird_ends_at'), 'Africa/Lagos')->toDateString()
                : null,
            'accelerator.installment_count' => Accelerator::installmentCount(),
        ];

        // Only what the landing page already shows: consented, published quotes,
        // verbatim. One string so it drops into a single KB placeholder.
        $testimonials = Accelerator::publishedTestimonials();
        $facts['accelerator.testimonials_count'] = $testimonials->count();
        $facts['accelerator.testimonials'] = $testimonials
            ->map(fn (array $t) => '- '.($t['name'] ?? 'A student').': "'.trim((string) ($t['quote'] ?? '')).'"')
            ->implode("\n") ?: null;

        foreach (Accelerator::enabledCurrencies() as $currency) {
            $facts["accelerator.price_full.{$currency}"] = $this->money(Accelerator::fullPrice($currency), $currency);
            $facts["accelerator.price_regular.{$currency}"] = $this->money(Accelerator::regularFullPrice($currency), $currency);
            $facts["accelerator.installment_each.{$currency}"] = $this->money(Accelerator::installmentEach($currency), $currency);
        }

        $masterclass = (array) config('taab.masterclass', []);
        $startsAt = ! empty($masterclass['starts_at'])
            ? Carbon::parse($masterclass['starts_at'], $masterclass['timezone'] ?? 'Africa/Lagos')
            : null;

        $facts += [
            'masterclass.date'                => $masterclass['date'] ?? null,
            'masterclass.time'                => $masterclass['time'] ?? null,
            'masterclass.timezone'            => $masterclass['timezone'] ?? null,
            'masterclass.registration_closes' => $masterclass['registration_closes'] ?? null,
            'masterclass.is_upcoming'         => $startsAt !== null && $now->lt($startsAt),
            'generated_at'                    => $now->toIso8601String(),
        ];

        return $facts;
    }

    /** "₦120,000" — formatted here so the AI quotes the same string the site shows. */
    private function money(float $amount, string $currency): string
    {
        return Accelerator::currencySymbol($currency).number_format($amount);
    }
}
