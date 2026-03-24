<?php

namespace Platform\Canvas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class CanvasComment extends Model
{
    protected $table = 'canvas_comments';

    protected $fillable = [
        'uuid',
        'canvas_id',
        'building_block_id',
        'content',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = UuidV7::generate();
            }
        });
    }

    public function canvas(): BelongsTo
    {
        return $this->belongsTo(Canvas::class, 'canvas_id');
    }

    public function buildingBlock(): BelongsTo
    {
        return $this->belongsTo(BuildingBlock::class, 'building_block_id');
    }
}
