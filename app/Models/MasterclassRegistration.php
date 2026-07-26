<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterclassRegistration extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'whatsapp',
        'background',
        'goal',
        'session_date',
        'status',
        'reminder_sent_at',
        'dayof_sent_at',
        'followup_sent_at',
        'attended',
        'attended_at',
    ];

    protected $casts = [
        'reminder_sent_at' => 'datetime',
        'dayof_sent_at' => 'datetime',
        'followup_sent_at' => 'datetime',
        'attended' => 'boolean',
        'attended_at' => 'datetime',
    ];
}
