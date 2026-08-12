<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Snippet extends Model
{
    protected $fillable = [
        'title',
        'body',
        'language',
        'module_id',
        'is_published',
        'position',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'position' => 'integer',
    ];

    /** Labels for the type pill. Keys are what's stored. */
    public const LANGUAGES = [
        'prompt' => 'AI Prompt',
        'json' => 'JSON',
        'javascript' => 'JavaScript',
        'bash' => 'Shell',
        'text' => 'Text',
    ];

    /**
     * Published snippets a student should see on a given module: the ones pinned
     * to that module, plus the global ones (module_id null).
     *
     * @return Collection<int, Snippet>
     */
    public static function visibleFor(?string $moduleId): Collection
    {
        return static::query()
            ->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('module_id')->orWhere('module_id', $moduleId))
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function languageLabel(): string
    {
        return self::LANGUAGES[$this->language] ?? 'Text';
    }

    /**
     * Curriculum title for the attached module, or a global marker. Module ids are
     * config keys, so a snippet can outlive its module - say so rather than
     * rendering a raw id at an admin.
     */
    public function moduleLabel(): string
    {
        if (! $this->module_id) {
            return 'All modules';
        }

        $module = collect(config('curriculum.core', []))->firstWhere('id', $this->module_id);

        return $module['title'] ?? "Unknown module ({$this->module_id})";
    }
}
