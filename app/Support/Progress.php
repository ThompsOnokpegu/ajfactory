<?php

namespace App\Support;

use App\Models\Checkpoint;
use App\Models\Enrollment;
use App\Models\LiveAttendance;
use Illuminate\Support\Collection;

/**
 * Per-student progress and the cohort leaderboard.
 *
 * One home for "how far along is this student", read by both the student dashboard
 * (their own row + the leaderboard) and the admin progress screen. Keeping the
 * scoring in one place stops the two screens from disagreeing about who is ahead.
 *
 * WHAT COUNTS (deliberate - see the ranking rules below):
 *   - Approved checkpoints on CORE modules. Verified by a human, so it can't be gamed.
 *   - Live sessions attended. Verified by a code announced only on the call.
 *
 * WHAT DOESN'T: `completed_lessons`. Those are self-marked ticks - a student can
 * click through the whole course in a minute. They're shown as progress, never ranked.
 */
class Progress
{
    /**
     * Core module ids, in curriculum order. These are the modules that carry
     * checkpoints; the Live Archive does not.
     *
     * @return array<int, string>
     */
    public static function coreModuleIds(): array
    {
        return collect(config('curriculum.core', []))
            ->pluck('id')
            ->filter()
            ->values()
            ->all();
    }

    /** Lesson ids across the whole curriculum - the denominator for "lessons completed". */
    public static function totalLessonCount(): int
    {
        return collect(config('curriculum.core', []))
            ->merge(config('curriculum.live', []))
            ->sum(fn ($m) => count($m['videos'] ?? []));
    }

    /**
     * Progress rows for a cohort, ranked.
     *
     * Ranking, in order:
     *   1. Most approved core checkpoints  - how far they've actually shipped.
     *   2. Most live sessions attended     - tiebreak on showing up.
     *   3. Earliest last approval          - of two students on the same count, the
     *                                        one who got there first ranks higher, so
     *                                        the board rewards pace, not just arrival.
     *
     * Only `status = 'paid'` enrollments appear: a pending/abandoned checkout is not a
     * student and must never show up on a board their cohort can see. Suspended students
     * (behind on an installment) DO appear - they're still enrolled, and quietly deleting
     * someone from the board over a late payment is a worse message than the payment nudge.
     *
     * @return Collection<int, array{enrollment_id:int, name:string, display_name:string, approved:int, live:int, lessons:int, last_approved_at:?\Illuminate\Support\Carbon, rank:int}>
     */
    public static function forCohort(int $cohort): Collection
    {
        $coreIds = self::coreModuleIds();

        $enrollments = Enrollment::query()
            ->where('cohort', $cohort)
            ->where('status', 'paid')
            ->get(['id', 'full_name', 'email', 'cohort', 'completed_lessons']);

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $ids = $enrollments->pluck('id');

        // Approved CORE checkpoints only, plus when each student's latest approval landed.
        $checkpoints = Checkpoint::query()
            ->whereIn('enrollment_id', $ids)
            ->where('status', 'approved')
            ->whereIn('module_id', $coreIds)
            ->selectRaw('enrollment_id, COUNT(*) as approved, MAX(reviewed_at) as last_approved_at')
            ->groupBy('enrollment_id')
            ->get()
            ->keyBy('enrollment_id');

        $attendance = LiveAttendance::query()
            ->whereIn('enrollment_id', $ids)
            ->selectRaw('enrollment_id, COUNT(*) as live')
            ->groupBy('enrollment_id')
            ->get()
            ->keyBy('enrollment_id');

        return $enrollments
            ->map(function (Enrollment $e) use ($checkpoints, $attendance) {
                $cp = $checkpoints[$e->id] ?? null;
                $lastApproved = $cp?->last_approved_at;

                return [
                    'enrollment_id'    => $e->id,
                    'name'             => $e->full_name ?? '',
                    'display_name'     => self::displayName($e->full_name),
                    'email'            => $e->email,
                    'approved'         => (int) ($cp->approved ?? 0),
                    'live'             => (int) ($attendance[$e->id]->live ?? 0),
                    'lessons'          => is_array($e->completed_lessons) ? count($e->completed_lessons) : 0,
                    'last_approved_at' => $lastApproved ? \Illuminate\Support\Carbon::parse($lastApproved) : null,
                ];
            })
            ->sort(function (array $a, array $b) {
                // 1. more approved checkpoints first
                if ($a['approved'] !== $b['approved']) {
                    return $b['approved'] <=> $a['approved'];
                }
                // 2. more live sessions first
                if ($a['live'] !== $b['live']) {
                    return $b['live'] <=> $a['live'];
                }
                // 3. whoever reached that count first. Nobody-has-shipped-yet (null)
                //    sorts last so a student with zero approvals never outranks one
                //    who has shipped, whatever the timestamps say.
                $at = $a['last_approved_at']?->getTimestamp();
                $bt = $b['last_approved_at']?->getTimestamp();
                if ($at === $bt) return strcasecmp($a['name'], $b['name']);
                if ($at === null) return 1;
                if ($bt === null) return -1;

                return $at <=> $bt;
            })
            ->values()
            ->map(fn (array $row, int $i) => $row + ['rank' => $i + 1]);
    }

    /**
     * Leaderboard as the student sees it: the top N, plus their own row always,
     * even when they're nowhere near the top.
     *
     * `you` is null when the viewer isn't a paid member of that cohort (an admin
     * previewing, say), which is why the caller must handle a missing row rather
     * than assuming the viewer is on the board.
     *
     * @return array{top: Collection, you: ?array, total: int}
     */
    public static function leaderboardFor(int $cohort, ?int $enrollmentId, int $topN = 10): array
    {
        $rows = self::forCohort($cohort);

        return [
            'top'   => $rows->take($topN),
            'you'   => $enrollmentId ? $rows->firstWhere('enrollment_id', $enrollmentId) : null,
            'total' => $rows->count(),
        ];
    }

    /**
     * "Chidi Okonkwo" -> "Chidi O." - recognisable to cohort mates who already know
     * each other from Telegram, without publishing full names on a shared page.
     */
    public static function displayName(?string $fullName): string
    {
        $parts = preg_split('/\s+/', trim((string) $fullName)) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

        if ($parts === []) {
            return 'Student';
        }

        $first = $parts[0];

        if (count($parts) === 1) {
            return $first;
        }

        return $first . ' ' . strtoupper(mb_substr(end($parts), 0, 1)) . '.';
    }
}
