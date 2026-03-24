<?php

namespace Platform\Canvas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class BuildingBlock extends Model
{
    protected $table = 'canvas_building_blocks';

    protected $fillable = [
        'uuid',
        'canvas_id',
        'block_key',
        'label',
        'position',
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

    public function canvas(): BelongsTo
    {
        return $this->belongsTo(Canvas::class, 'canvas_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'building_block_id')->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CanvasComment::class, 'building_block_id');
    }

    // Scopes

    public function scopeByKey($query, string $key)
    {
        return $query->where('block_key', $key);
    }

    /**
     * Get guiding questions for this block from the canvas type.
     */
    public function getGuidingQuestions(): array
    {
        $this->loadMissing('canvas.canvasType');

        return $this->canvas?->canvasType?->getGuidingQuestions($this->block_key) ?? [];
    }
}
