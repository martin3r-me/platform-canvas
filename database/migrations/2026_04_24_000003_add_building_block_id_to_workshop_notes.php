<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canvas_workshop_notes', function (Blueprint $table) {
            $table->foreignId('building_block_id')->nullable()->after('canvas_id')
                ->constrained('canvas_building_blocks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('canvas_workshop_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('building_block_id');
        });
    }
};
