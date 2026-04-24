<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canvas_workshop_notes', function (Blueprint $table) {
            $table->string('type', 20)->default('note')->after('color');
            $table->json('metadata')->nullable()->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('canvas_workshop_notes', function (Blueprint $table) {
            $table->dropColumn(['type', 'metadata']);
        });
    }
};
