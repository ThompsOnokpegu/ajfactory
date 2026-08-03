<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePurchase extends Model
{
    protected $fillable = [
        'resource_id',
        'name',
        'email',
        'whatsapp',
        'payment_reference',
        'access_token',
        'amount',
        'currency',
        'status',
        'paid_at',
    ];

    /** Route-model binding for the gated access page uses the token, not the id. */
    public function getRouteKeyName(): string
    {
        return 'access_token';
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
