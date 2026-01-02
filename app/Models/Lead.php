<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    //
    protected $fillable = [
        'name',
        'business_name',
        'email',
        'phone',
        'password',
        'contact_preference',
        'estimated_leads_per_month',
        'status',
        'factory_notes',
    ];
}
