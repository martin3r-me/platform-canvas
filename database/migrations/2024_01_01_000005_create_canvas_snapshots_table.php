<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('canvas_id')->constrained('canvases')->onDelete('cascade');
            $table->integer('version');
            $table->json('snapshot_data');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['canvas_id', 'version'], 'canvas_snapshots_canvas_version_uq');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_snapshots');
    }
};
