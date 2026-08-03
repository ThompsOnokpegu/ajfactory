<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = [
        'title', 'description', 'category', 'url', 'price', 'price_usd', 'emoji', 'is_published', 'is_pinned', 'sort_order', 'clicks',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_pinned' => 'boolean',
        'price' => 'decimal:2',
        'price_usd' => 'decimal:2',
    ];

    /** Published resources — pinned first, then the owner's chosen order. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    /** A resource is paid when it carries a positive NGN price. */
    public function isPaid(): bool
    {
        return (float) $this->price > 0;
    }

    /** Price in the given currency, or null if it isn't sold in that currency. */
    public function priceFor(string $currency): ?float
    {
        if ($currency === 'USD') {
            return $this->price_usd !== null ? (float) $this->price_usd : null;
        }

        return $this->price !== null ? (float) $this->price : null;
    }
}
