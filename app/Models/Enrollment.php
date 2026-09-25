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
        'coupon_code',
        'discount_amount',
        'status',
        'paystack_payload',
        'paid_at',
        'completed_lessons',
        'meta_context',           // {fbp, fbc, ip, ua, captured_at} from the checkout request
        'meta_purchase_sent_at',  // stamped only when the Meta Conversions API accepted the Purchase
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
        'meta_context' => 'array',
        'meta_purchase_sent_at' => 'datetime',
    ];

    /**
     * THE enrollment row for a signed-in student. Use this everywhere - never
     * `Enrollment::where('email', ...)->first()`.
     *
     * A student accumulates rows: checkout writes a fresh `pending` row on every
     * attempt and only the one matching the payment reference is flipped to `paid`,
     * so anyone who abandoned a checkout once has an old pending row sitting in
     * front of their real one. An unfiltered `first()` returns that old row.
     *
     * That is exactly what went wrong: access was granted on the paid row (see
     * CheckEnrollment) while the dashboard read and wrote progress against the
     * pending one, so approved checkpoints were attached to a row the admin
     * progress screen - which counts paid rows only - could not see. Students who
     * had shipped several modules showed 0/9.
     *
     * Paid only, most recently paid first, so a returning student who re-enrolled
     * lands on their CURRENT cohort rather than the one they finished.
     */
    public static function currentFor(?string $email): ?self
    {
        if (! $email) {
            return null;
        }

        return static::where('email', $email)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class);
    }

    public function liveAttendances(): HasMany
    {
        return $this->hasMany(LiveAttendance::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StudentReview::class);
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