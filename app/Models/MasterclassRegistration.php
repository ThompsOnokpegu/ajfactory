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
    ];
}
