<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('building_block_id')->constrained('canvas_building_blocks')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('entry_type', 50)->default('text');
            $table->integer('position')->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['building_block_id', 'position'], 'canvas_entries_block_pos_idx');
            $table->index(['building_block_id', 'entry_type'], 'canvas_entries_block_type_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_entries');
    }
};
