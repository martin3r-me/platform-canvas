<?php

namespace Platform\Canvas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class Canvas extends Model
{
    use LogsActivity, SoftDeletes;

    // Status-Konstanten (Funnel-Reihenfolge)
    public const STATUS_BACKLOG = 'backlog';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_BACKLOG,
        self::STATUS_IN_PROGRESS,
        self::STATUS_REVIEW,
        self::STATUS_VALIDATED,
        self::STATUS_ARCHIVED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_BACKLOG => 'Backlog',
        self::STATUS_IN_PROGRESS => 'In Arbeit',
        self::STATUS_REVIEW => 'Review',
        self::STATUS_VALIDATED => 'Validiert',
        self::STATUS_ARCHIVED => 'Archiviert',
    ];

    public const STATUS_ICONS = [
        self::STATUS_BACKLOG => 'heroicon-o-inbox-stack',
        self::STATUS_IN_PROGRESS => 'heroicon-o-pencil-square',
        self::STATUS_REVIEW => 'heroicon-o-eye',
        self::STATUS_VALIDATED => 'heroicon-o-check-badge',
        self::STATUS_ARCHIVED => 'heroicon-o-archive-box',
    ];

    public const STATUS_VARIANTS = [
        self::STATUS_BACKLOG => 'secondary',
        self::STATUS_IN_PROGRESS => 'warning',
        self::STATUS_REVIEW => 'info',
        self::STATUS_VALIDATED => 'success',
        self::STATUS_ARCHIVED => 'secondary',
    ];

    protected $table = 'canvases';

    protected $fillable = [
        'uuid',
        'team_id',
        'canvas_type_id',
        'name',
        'description',
        'status',
        'public_token',
        'is_public',
        'contextable_type',
        'contextable_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'status' => 'string',
        'is_public' => 'boolean',
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
}
