<?php

namespace Platform\Canvas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class Entry extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'canvas_entries';

    protected $fillable = [
        'uuid',
        'building_block_id',
        'title',
        'content',
        'entry_type',
        'position',
        'metadata',
        'created_by_user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
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

    public function buildingBlock(): BelongsTo
    {
        return $this->belongsTo(BuildingBlock::class, 'building_block_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    /**
     * Get the allowed entry types from the canvas type.
     */
    public static function getEntryTypes(): array
    {
        return ['text', 'date', 'person', 'amount'];
    }
}
