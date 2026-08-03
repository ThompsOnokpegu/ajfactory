<?php

namespace App\Support;

use App\Models\ResourcePurchase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fires the n8n `resource_purchased` event so the buyer is emailed their unlocked
 * link. Shared by the Paystack + Flutterwave webhooks. Delivery is best-effort —
 * the access page (`resources.access`) reveals the link once the purchase is paid,
 * so a dropped email never locks the buyer out.
 */
class ResourceDelivery
{
    public static function deliver(ResourcePurchase $purchase): void
    {
        $url = config('services.n8n.student_webhook_url');
        if (! $url) {
            return;
        }

        try {
            Http::timeout(45)->post($url, [
                'type' => 'resource_purchased',
                'name' => $purchase->name,
                'first_name' => strtok((string) $purchase->name, ' ') ?: 'there',
                'email' => $purchase->email,
                'whatsapp' => $purchase->whatsapp,
                'resource_title' => $purchase->resource?->title,
                'resource_url' => $purchase->resource?->url,
                'access_url' => route('resources.access', $purchase),
                'amount' => $purchase->amount,
                'currency' => $purchase->currency,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Resource delivery webhook failed for {$purchase->email}: " . $e->getMessage());
        }
    }
}
