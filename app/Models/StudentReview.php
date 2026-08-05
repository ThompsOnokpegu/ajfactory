<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentReview extends Model
{
    protected $fillable = [
        'enrollment_id',
        'stage',
        'status',
        'rating',
        'answers',
        'consent_public',
        'credit_as',
        'dismiss_count',
        'dismissed_at',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'consent_public' => 'boolean',
        'rating' => 'integer',
        'dismiss_count' => 'integer',
        'dismissed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * True when this response is safe to quote publicly: the student answered,
     * ticked the consent box, and wasn't unhappy. Anything else is internal.
     */
    public function isUsablePublicly(): bool
    {
        return $this->isSubmitted()
            && $this->consent_public
            && (int) $this->rating > (int) config('reviews.unhappy_at_or_below', 3);
    }

    /**
     * How to attribute a public quote, honouring the student's credit choice.
     */
    public function creditLine(): string
    {
        $name = trim((string) ($this->enrollment->full_name ?? ''));
        $cohort = $this->enrollment->cohort ? "Cohort {$this->enrollment->cohort}" : 'Accelerator student';

        if ($this->credit_as === 'anon' || $name === '') {
            return "Accelerator student, {$cohort}";
        }

        if ($this->credit_as === 'first') {
            $parts = preg_split('/\s+/', $name);
            $first = $parts[0];
            $initial = count($parts) > 1 ? ' '.strtoupper(substr(end($parts), 0, 1)).'.' : '';

            return "{$first}{$initial}, {$cohort}";
        }

        return "{$name}, {$cohort}";
    }
}
