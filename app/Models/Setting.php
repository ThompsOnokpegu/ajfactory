<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Runtime key/value settings (admin-flippable toggles). Read live so changes
 * apply instantly — no config:cache needed.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $row = static::query()->where('key', $key)->first();

        return $row ? $row->value : $default;
    }

    public static function put(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    /** Read a boolean flag (stored as '1'/'0'); returns $default when unset. */
    public static function flag(string $key, bool $default = true): bool
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        return in_array($value, ['1', 1, true, 'true'], true);
    }
}
