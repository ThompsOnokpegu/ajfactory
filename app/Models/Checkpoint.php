<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkpoint extends Model
{
    protected $fillable = [
        'enrollment_id',
        'module_id',
        'status',
        'proof_url',
        'note',
        'submitted_at',
        'reviewed_at',
        'student_seen_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'student_seen_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Has this checkpoint been reviewed since the student last acknowledged it?
     *
     * Compares the two timestamps rather than reading a flag, so a re-review after
     * a resubmission announces itself even though the student dismissed the earlier
     * one: reject -> resubmit -> approve pushes reviewed_at past student_seen_at.
     */
    public function isUnseenReview(): bool
    {
        if (! $this->reviewed_at) {
            return false;
        }

        return $this->student_seen_at === null
            || $this->student_seen_at->lt($this->reviewed_at);
    }

    /** Checkpoints whose review the student has not acknowledged yet. */
    public function scopeUnseenReview($query)
    {
        return $query->whereNotNull('reviewed_at')
            ->where(function ($q) {
                $q->whereNull('student_seen_at')
                  ->orWhereColumn('student_seen_at', '<', 'reviewed_at');
            });
    }
}
