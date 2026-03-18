<?php

namespace Platform\Canvas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class CanvasType extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'canvas_types';

    protected $fillable = [
        'uuid',
        'team_id',
        'key',
        'name',
        'description',
        'purpose',
        'methodology',
        'icon',
        'block_definitions',
        'layout',
        'entry_types',
        'analysis_config',
        'origin',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'block_definitions' => 'array',
        'layout' => 'array',
        'entry_types' => 'array',
        'analysis_config' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
        });
    }

    // Relationships

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function canvases(): HasMany
    {
        return $this->hasMany(Canvas::class, 'canvas_type_id');
    }

    // Scopes

    public function scopeAvailableForTeam($query, int $teamId)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($teamId) {
                $q->whereNull('team_id')
                    ->orWhere('team_id', $teamId);
            });
    }

    public function scopeSystem($query)
    {
        return $query->where('origin', 'system')->whereNull('team_id');
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    // Helpers

    public function getBlockDefinition(string $key): ?array
    {
        foreach ($this->block_definitions as $def) {
            if (($def['key'] ?? null) === $key) {
                return $def;
            }
        }

        return null;
    }

    public function getGuidingQuestions(string $blockKey): array
    {
        $def = $this->getBlockDefinition($blockKey);

        return $def['guiding_questions'] ?? [];
    }

    public function isSystem(): bool
    {
        return $this->origin === 'system';
    }
}
