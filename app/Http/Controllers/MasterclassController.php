<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MasterclassController extends Controller
{
    /**
     * Register an attendee for the current TAAB masterclass session. Stores the
     * full event registration, mirrors the lead into the `students` waitlist
     * (so the funnel + Accelerator checkout recognise them), and notifies n8n.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'whatsapp' => 'nullable|string|max:40',
            'background' => 'nullable|string|max:255',
            'goal' => 'nullable|string|max:255',
        ]);

        $email = strtolower(trim($data['email']));
        $session = config('taab.masterclass.date');

        DB::table('masterclass_registrations')->updateOrInsert(
            ['email' => $email, 'session_date' => $session],
            [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'background' => $data['background'] ?? null,
                'goal' => $data['goal'] ?? null,
                'status' => 'registered',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $this->mirrorToStudents($data, $email);
        $this->notifyN8n($data, $email, $session);

        return response()->json(['ok' => true]);
    }

    private function mirrorToStudents(array $data, string $email): void
    {
        $values = [
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'whatsapp' => $data['whatsapp'] ?? null,
            'interest' => 'masterclass',
            'source' => 'registration',
            'updated_at' => now(),
        ];

        if (DB::table('students')->where('email', $email)->exists()) {
            DB::table('students')->where('email', $email)->update($values);
        } else {
            DB::table('students')->insert($values + ['email' => $email, 'created_at' => now()]);
        }
    }

    private function notifyN8n(array $data, string $email, ?string $session): void
    {
        $url = config('services.n8n.student_webhook_url');
        if (! $url) {
            return;
        }

        try {
            Http::post($url, [
                'type' => 'masterclass_registration',
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'email' => $email,
                'whatsapp' => $data['whatsapp'] ?? null,
                'background' => $data['background'] ?? null,
                'goal' => $data['goal'] ?? null,
                'session_date' => $session,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Masterclass registration webhook failed: ' . $e->getMessage());
        }
    }
}
