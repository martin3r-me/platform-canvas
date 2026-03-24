<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_comments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('canvas_id')->constrained('canvases')->cascadeOnDelete();
            $table->foreignId('building_block_id')->nullable()->constrained('canvas_building_blocks')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();

            $table->index(['canvas_id', 'building_block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_comments');
    }
};
