<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VapiWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Log incoming data (Useful for debugging)
        Log::info('Vapi Webhook Received', $request->all());

        $payload = $request->all();

        // 2. Only process the "End of Call" report
        if (($payload['message']['type'] ?? '') === 'end-of-call-report') {
            
            $report = $payload['message'];
            $phone = $report['customer']['number'] ?? null;

            if ($phone) {
                // Find the most recent lead with this phone number
                // Note: In production, stripping +1 or country codes for matching is safer
                $lead = Lead::where('phone', 'LIKE', '%' . substr($phone, -10))->latest()->first();

                if ($lead) {
                    $lead->update([
                        'status' => 'processed',
                        'transcript' => $report['artifact']['transcript'] ?? null,
                        'call_summary' => $report['analysis']['summary'] ?? null,
                        'recording_url' => $report['artifact']['recordingUrl'] ?? null,
                        'call_duration' => $report['durationSeconds'] ?? 0,
                        'analysis_data' => $report['analysis']['structuredData'] ?? null,
                    ]);

                    Log::info("Lead #{$lead->id} updated with call data.");
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}