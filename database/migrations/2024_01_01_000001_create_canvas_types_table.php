<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canvas_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
            $table->string('key', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('purpose')->nullable();
            $table->string('methodology')->nullable();
            $table->string('icon', 100)->nullable();
            $table->json('block_definitions');
            $table->json('layout');
            $table->json('entry_types')->default('["text"]');
            $table->json('analysis_config')->nullable();
            $table->enum('origin', ['system', 'custom'])->default('custom');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'key'], 'canvas_types_team_key_uq');
            $table->index('uuid');
            $table->index('origin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canvas_types');
    }
};
