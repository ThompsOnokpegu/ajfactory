<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaabLeadController extends Controller
{
    /**
     * Capture a TAAB lead-magnet lead into the `students` waitlist and notify
     * n8n. Mirrors the student-waitlist Volt component, tagged with the source
     * tool so the funnel can attribute it.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'whatsapp' => 'nullable|string|max:40',
            'source' => 'required|in:scorecard,roi,tool-stack',
        ]);

        $email = strtolower(trim($data['email']));

        $values = [
            'name' => $data['name'],
            'whatsapp' => $data['whatsapp'] ?? null,
            'interest' => 'masterclass',
            'source' => $data['source'],
            'updated_at' => now(),
        ];

        if (DB::table('students')->where('email', $email)->exists()) {
            DB::table('students')->where('email', $email)->update($values);
        } else {
            DB::table('students')->insert($values + ['email' => $email, 'created_at' => now()]);
        }

        $this->notifyN8n($data, $email);

        return response()->json(['ok' => true]);
    }

    private function notifyN8n(array $data, string $email): void
    {
        $url = config('services.n8n.student_webhook_url');
        if (! $url) {
            return;
        }

        try {
            Http::post($url, [
                'type' => 'taab_lead',
                'source' => $data['source'],
                'name' => $data['name'],
                'email' => $email,
                'whatsapp' => $data['whatsapp'] ?? null,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('TAAB lead webhook failed: ' . $e->getMessage());
        }
    }
}
