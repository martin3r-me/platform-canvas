<?php

namespace Platform\Canvas\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Platform\Core\Models\User;
use Platform\Core\Traits\HasColors;
use Platform\Core\Traits\HasTags;
use Platform\Core\Contracts\AgendaRenderable;
use Symfony\Component\Uid\UuidV7;

class Canvas extends Model implements AgendaRenderable
{
    use LogsActivity, SoftDeletes, HasTags, HasColors;

    // Visibility-Konstanten
    public const VISIBILITY_TEAM = 'team';
    public const VISIBILITY_PRIVATE = 'private';

    // Status-Konstanten
    public const STATUS_OPEN = 'open';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DISCARDED = 'discarded';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_COMPLETED,
        self::STATUS_DISCARDED,
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_OPEN,
    ];

    public const DONE_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_DISCARDED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_OPEN => 'Offen',
        self::STATUS_COMPLETED => 'Abgeschlossen',
        self::STATUS_DISCARDED => 'Verworfen',
    ];

    public const STATUS_ICONS = [
        self::STATUS_OPEN => 'heroicon-o-pencil-square',
        self::STATUS_COMPLETED => 'heroicon-o-check-circle',
        self::STATUS_DISCARDED => 'heroicon-o-x-circle',
    ];

    public const STATUS_VARIANTS = [
        self::STATUS_OPEN => 'primary',
        self::STATUS_COMPLETED => 'success',
        self::STATUS_DISCARDED => 'secondary',
    ];

    protected $table = 'canvases';

    protected $fillable = [
        'uuid',
        'team_id',
        'canvas_type_id',
        'name',
        'description',
        'status',
        'visibility',
        'public_token',
        'is_public',
        'workshop_settings',
        'contextable_type',
        'contextable_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'status' => 'string',
        'is_public' => 'boolean',
        'workshop_settings' => 'array',
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

    public function canvasType(): BelongsTo
    {
        return $this->belongsTo(CanvasType::class, 'canvas_type_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function contextable(): MorphTo
    {
        return $this->morphTo();
    }

    public function buildingBlocks(): HasMany
    {
        return $this->hasMany(BuildingBlock::class, 'canvas_id')->orderBy('position');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(CanvasSnapshot::class, 'canvas_id')->orderBy('version', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CanvasComment::class, 'canvas_id');
    }

    public function workshopNotes(): HasMany
    {
        return $this->hasMany(WorkshopNote::class, 'canvas_id');
    }

    // Scopes

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOfType($query, string $typeKey)
    {
        return $query->whereHas('canvasType', fn ($q) => $q->where('key', $typeKey));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', self::VISIBILITY_TEAM)
              ->orWhere('created_by_user_id', $user->id);
        });
    }

    public function isVisibleTo(User $user): bool
    {
        return $this->visibility === self::VISIBILITY_TEAM
            || $this->created_by_user_id === $user->id;
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->whereNotNull('public_token');
    }

    public function generatePublicToken(): string
    {
        $this->public_token = bin2hex(random_bytes(16));
        $this->is_public = true;
        $this->save();

        return $this->public_token;
    }

    public function getPublicUrl(): ?string
    {
        if (! $this->public_token) {
            return null;
        }

        return route('canvas.public.show', $this->public_token);
    }

    // Status helpers

    public function close(?string $reason = 'completed'): void
    {
        $status = $reason === 'discarded' ? self::STATUS_DISCARDED : self::STATUS_COMPLETED;
        $this->update(['status' => $status]);
    }

    public function reopen(): void
    {
        $this->update(['status' => self::STATUS_OPEN]);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::DONE_STATUSES);
    }

    /**
     * Initialize building blocks from the canvas type's block_definitions.
     */
    public function initializeBlocks(): void
    {
        $this->loadMissing('canvasType');
        $definitions = $this->canvasType->block_definitions ?? [];

        foreach ($definitions as $definition) {
            $this->buildingBlocks()->create([
                'block_key' => $definition['key'],
                'label' => $definition['label'],
                'position' => $definition['position'] ?? 0,
            ]);
        }
    }

    /**
     * Export the full canvas data as an array.
     */
    public function toCanvasArray(): array
    {
        $this->loadMissing(['canvasType', 'buildingBlocks.entries']);

        $blocks = [];
        foreach ($this->buildingBlocks as $block) {
            $blocks[$block->block_key] = [
                'id' => $block->id,
                'label' => $block->label,
                'position' => $block->position,
                'entries' => $block->entries->map(fn (Entry $e) => [
                    'id' => $e->id,
                    'uuid' => $e->uuid,
                    'title' => $e->title,
                    'content' => $e->content,
                    'entry_type' => $e->entry_type,
                    'position' => $e->position,
                    'metadata' => $e->metadata,
                ])->values()->toArray(),
            ];
        }

        return [
            'canvas' => [
                'id' => $this->id,
                'uuid' => $this->uuid,
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
                'canvas_type_key' => $this->canvasType?->key,
                'canvas_type_name' => $this->canvasType?->name,
                'team_id' => $this->team_id,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            'blocks' => $blocks,
        ];
    }

    // ── AgendaRenderable ──────────────────────────────────────

    public function toAgendaItem(): array
    {
        return [
            'title' => $this->name,
            'description' => $this->description ? \Illuminate\Support\Str::limit($this->description, 120) : null,
            'icon' => '🎨',
            'color' => $this->color,
            'status' => $this->status,
            'status_color' => match ($this->status) {
                'completed' => 'green',
                'discarded' => 'gray',
                default => 'blue',
            },
            'url' => route('canvas.canvases.show', $this),
            'meta' => ['canvas_type' => $this->canvasType?->name],
        ];
    }
}
