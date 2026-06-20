<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Waitlist + TAAB lead-magnet captures (the `students` table). Written by the
 * student-waitlist form, TaabLeadController, and mirrored from masterclass regs.
 */
class Student extends Model
{
    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'interest',
        'source',
    ];
}
