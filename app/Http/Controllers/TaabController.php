<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaabController extends Controller
{
    /**
     * The TAAB hub + registration page. When arrived at via a re-invite link
     * (/taab?i=<token>), recognise the returning lead and pre-fill what they've
     * already given us so registering is near-frictionless. Unknown/absent token
     * → a blank form, exactly as before.
     *
     * Deliberately does NOT pre-fill `goal`: re-capturing a fresh goal is the
     * whole reason re-invites drive people to register rather than auto-enrolling.
     */
    public function hub(Request $request): View
    {
        return view('taab.index', [
            'prefill' => $this->prefillFor($request->query('i')),
        ]);
    }

    /**
     * Resolve an invite token to the best data we hold for that person.
     * Identity + background come from their most recent registration if any
     * (past registrant), else the waitlist lead row. `goal` is never returned.
     *
     * @return array<string, string>
     */
    private function prefillFor(?string $token): array
    {
        $token = trim((string) $token);
        if ($token === '') {
            return [];
        }

        $invite = DB::table('masterclass_invites')->where('token', $token)->first();
        if (! $invite) {
            return [];
        }

        $email = strtolower(trim($invite->email));
        $prefill = ['email' => $email];

        // Richest source first: a prior registration carries names + background.
        $reg = DB::table('masterclass_registrations')
            ->where('email', $email)
            ->orderByDesc('created_at')
            ->first();

        if ($reg) {
            return $prefill + array_filter([
                'first_name' => $reg->first_name,
                'last_name' => $reg->last_name,
                'whatsapp' => $reg->whatsapp,
                'background' => $reg->background,
            ], fn ($v) => $v !== null && $v !== '');
        }

        // Otherwise a waitlist lead: only a single `name` + whatsapp.
        $student = DB::table('students')->where('email', $email)->first();
        if ($student) {
            [$first, $last] = $this->splitName($student->name);
            return $prefill + array_filter([
                'first_name' => $first,
                'last_name' => $last,
                'whatsapp' => $student->whatsapp,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return $prefill;
    }

    /** First word → first name, the remainder → last name. */
    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
