<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'completed_lessons' => 'array',
    ];
}