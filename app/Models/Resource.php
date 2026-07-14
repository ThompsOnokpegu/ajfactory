<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = [
        'title', 'description', 'category', 'url', 'emoji', 'is_published', 'sort_order', 'clicks',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /** Published resources, in the owner's chosen order. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }
}
