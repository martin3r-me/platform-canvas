<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_building_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('canvas_id')->constrained('canvases')->onDelete('cascade');
            $table->string('block_key');
            $table->string('label');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['canvas_id', 'block_key'], 'canvas_bb_canvas_key_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_building_blocks');
    }
};
