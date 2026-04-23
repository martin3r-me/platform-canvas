<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_workshop_notes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('canvas_id')->constrained('canvases')->cascadeOnDelete();
            $table->string('title')->default('');
            $table->text('content')->nullable();
            $table->string('color', 20)->default('yellow');
            $table->float('position_x')->default(0);
            $table->float('position_y')->default(0);
            $table->unsignedInteger('width')->default(200);
            $table->unsignedInteger('height')->default(150);
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_workshop_notes');
    }
};
