<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScorecardController extends Controller
{
    /**
     * Capture a completed readiness-scorecard result. The email step gates the
     * results screen, so this fires for every finisher: it records the score/tier
     * on the students lead (enriching an existing lead without reclassifying it)
     * and notifies n8n so the owner can email the tailored next step.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'score' => 'required|integer|min:0|max:100',
            'tier' => 'required|in:ready,almost,not_yet',
            'dimensions' => 'nullable|array',
            'hosting_blocked' => 'boolean',
            'verdict_label' => 'nullable|string|max:120',
            'verdict_title' => 'nullable|string|max:200',
            'verdict_text' => 'nullable|string|max:2000',
            'steps' => 'nullable|array|max:10',
            'steps.*' => 'string|max:600',
        ]);

        $email = strtolower(trim($data['email']));
        $name = isset($data['name']) ? trim($data['name']) : null;

        $existing = DB::table('students')->where('email', $email)->first();

        if ($existing) {
            DB::table('students')->where('id', $existing->id)->update([
                'name' => $name ?: $existing->name,
                'scorecard_tier' => $data['tier'],
                'scorecard_score' => $data['score'],
                'updated_at' => now(),
            ]);
        } else {
            DB::table('students')->insert([
                'name' => $name ?: 'Scorecard lead',
                'email' => $email,
                'interest' => 'scorecard',
                'source' => 'scorecard',
                'scorecard_tier' => $data['tier'],
                'scorecard_score' => $data['score'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->notifyN8n($name, $email, $data);

        return response()->json(['ok' => true]);
    }

    private function notifyN8n(?string $name, string $email, array $data): void
    {
        $url = config('services.n8n.student_webhook_url');
        if (! $url) {
            return;
        }

        try {
            Http::post($url, [
                'type' => 'scorecard_result',
                'name' => $name,
                'email' => $email,
                'score' => $data['score'],
                'tier' => $data['tier'],
                'dimensions' => $data['dimensions'] ?? null,
                'hosting_blocked' => $data['hosting_blocked'] ?? false,
                // The verdict + next steps the user saw — ready to drop straight
                // into the "tailored next step" email.
                'verdict_label' => $data['verdict_label'] ?? null,
                'verdict_title' => $data['verdict_title'] ?? null,
                'verdict_text' => $data['verdict_text'] ?? null,
                'steps' => $data['steps'] ?? [],
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Scorecard result webhook failed: ' . $e->getMessage());
        }
    }
}
