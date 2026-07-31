<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'whatsapp',
        'payment_reference',
        'amount',
        'plan_type',
        'cohort',
        'amount_total',
        'balance_due',
        'second_payment_status',
        'second_payment_due_at',
        'second_payment_reference',
        'installment_reminder_sent_at',
        'access_suspended',
        'currency',
        'status',
        'paystack_payload',
        'paid_at',
        'completed_lessons',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    
    protected $casts = [
        'paystack_payload' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'amount_total' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'second_payment_due_at' => 'datetime',
        'installment_reminder_sent_at' => 'datetime',
        'access_suspended' => 'boolean',
        'cohort' => 'integer',
        'completed_lessons' => 'array',
    ];

    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class);
    }

    public function liveAttendances(): HasMany
    {
        return $this->hasMany(LiveAttendance::class);
    }

    /**
     * Cohort 2+ uses ship-to-unlock (proof-gated modules). Cohort 1 is legacy /
     * fully open — no checkpoint gating — so existing students are never locked
     * out of modules they already had.
     */
    public function usesShipToUnlock(): bool
    {
        return (int) $this->cohort >= 2;
    }

    /**
     * Module ids whose checkpoint this student has had approved.
     *
     * @return array<int, string>
     */
    public function approvedModuleIds(): array
    {
        return $this->checkpoints()
            ->where('status', 'approved')
            ->pluck('module_id')
            ->all();
    }
}