<?php

namespace App\Support;

use App\Models\Enrollment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side Purchase events to Meta's Conversions API.
 *
 * The browser pixel on /thank-you fires the same Purchase with eventID = the payment
 * reference; this sends it with event_id = the payment reference, so Meta keeps one.
 * The server event is the reliable one (the buyer often never reaches /thank-you) and
 * usually the one Meta keeps, so the match signals stored in `meta_context` at checkout
 * are what make it attributable.
 *
 * Same contract as every n8n send in this codebase: skip with a warning when
 * unconfigured, never throw, and stamp `meta_purchase_sent_at` ONLY when Meta genuinely
 * accepted the event. An unstamped paid enrollment is "still to send" and
 * meta:retry-purchases picks it up (Meta accepts events up to 7 days old).
 */
class MetaConversions
{
    public const CONTENT_NAME = 'AI Automation Accelerator';

    public function isConfigured(): bool
    {
        return (bool) (config('services.meta.pixel_id') && config('services.meta.access_token'));
    }

    /**
     * Fire the Purchase for a FIRST payment (full, early-bird, or 1st installment).
     *
     * Deliberately not fired for the 2nd installment or for guide sales: both would
     * count like a new course sale in the signal Meta optimises ads on.
     *
     * $gateway carries match signals from the gateway's verify payload (ip, phone,
     * country), used only to fill gaps in what the checkout captured.
     *
     * @param  array{ip?: ?string, phone?: ?string, country?: ?string}  $gateway
     * @return bool true when Meta accepted it (now or earlier); false = still to send.
     */
    public function purchase(Enrollment $enrollment, array $gateway = []): bool
    {
        try {
            if ($enrollment->meta_purchase_sent_at) {
                return true;   // idempotent under a replayed webhook or the retry command
            }

            if ($enrollment->status !== 'paid' || ! $enrollment->payment_reference) {
                return false;
            }

            if (! $this->isConfigured()) {
                Log::warning("Meta CAPI skipped for {$enrollment->payment_reference} - META_PIXEL_ID / META_ACCESS_TOKEN not set.");

                return false;
            }

            return $this->send($this->purchaseEvent($enrollment, $gateway), $enrollment);
        } catch (\Throwable $e) {
            // A webhook must never fail because of ad tracking.
            Log::error("Meta CAPI error for {$enrollment->payment_reference}: ".$e->getMessage());

            return false;
        }
    }

    /** @param array{ip?: ?string, phone?: ?string, country?: ?string} $gateway */
    private function purchaseEvent(Enrollment $enrollment, array $gateway): array
    {
        $context = array_filter((array) ($enrollment->meta_context ?? []));
        if (empty($context['ip']) && ! empty($gateway['ip'])) {
            $context['ip'] = $gateway['ip'];
        }

        $person = MetaUserData::fromEnrollment($enrollment);
        if (empty($person['phone']) && ! empty($gateway['phone'])) {
            $person['phone'] = MetaUserData::phone($gateway['phone']);
        }
        if (empty($person['country']) && ! empty($gateway['country'])) {
            $country = strtolower(trim((string) $gateway['country']));
            $person['country'] = preg_match('/^[a-z]{2}$/', $country) ? $country : null;
        }

        // Offline enrolments (manual / bank transfer) never touched the checkout, so
        // there is no browser session to claim. Meta wants that reported honestly.
        $fromBrowser = ! empty($context['fbp']) || ! empty($context['fbc']) || ! empty($context['ua']);

        $event = [
            'event_name' => 'Purchase',
            'event_time' => ($enrollment->paid_at ?? now())->getTimestamp(),
            'event_id' => $enrollment->payment_reference,   // = the browser Purchase's eventID
            'action_source' => $fromBrowser ? 'website' : 'other',
            'user_data' => MetaUserData::capiUserData($person, $context, 'enrollment:'.$enrollment->id),
            'custom_data' => [
                'value' => (float) $enrollment->amount,   // what was charged today, not the plan total
                'currency' => $enrollment->currency ?: 'NGN',
                'content_name' => self::CONTENT_NAME,
                'content_ids' => ['accelerator-'.($enrollment->plan_type ?: 'full')],
                'content_type' => 'product',
                'num_items' => 1,
                'order_id' => $enrollment->payment_reference,
            ],
        ];

        if ($fromBrowser) {
            $event['event_source_url'] = rtrim((string) config('app.public_url'), '/').'/thank-you';
        }

        return $event;
    }

    /** One HTTP call. True only on a 2xx that reports the event as received. */
    private function send(array $event, Enrollment $enrollment): bool
    {
        $ref = $event['event_id'];

        $body = [
            'data' => [$event],
            'access_token' => config('services.meta.access_token'),
        ];
        if ($code = config('services.meta.test_event_code')) {
            $body['test_event_code'] = $code;
        }

        try {
            $response = Http::timeout(20)->acceptJson()->post($this->endpoint(), $body);
        } catch (\Throwable $e) {
            Log::error("Meta CAPI request failed for {$ref}: ".$e->getMessage());

            return false;
        }

        if (! $response->successful() || (int) $response->json('events_received', 0) < 1) {
            // Log Meta's error object only - the request body carries the access token.
            $error = (array) ($response->json('error') ?? []);
            Log::error(sprintf(
                'Meta CAPI rejected %s: HTTP %d %s (code %s / subcode %s)',
                $ref,
                $response->status(),
                $error['message'] ?? 'no events_received',
                $error['code'] ?? '-',
                $error['error_subcode'] ?? '-',
            ));

            return false;
        }

        $enrollment->forceFill(['meta_purchase_sent_at' => now()])->save();

        return true;
    }

    private function endpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/events',
            config('services.meta.api_version', 'v25.0'),
            config('services.meta.pixel_id'),
        );
    }
}
