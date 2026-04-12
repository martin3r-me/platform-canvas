<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Data migration: map old statuses to new ones
        DB::table('canvases')->whereIn('status', ['backlog', 'in_progress', 'review'])->update(['status' => 'open']);
        DB::table('canvases')->where('status', 'validated')->update(['status' => 'completed']);
        DB::table('canvases')->where('status', 'archived')->update(['status' => 'discarded']);

        // 2. Change enum column
        Schema::table('canvases', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->change();
        });
    }

    public function down(): void
    {
        // Revert: map new statuses back to old ones
        DB::table('canvases')->where('status', 'open')->update(['status' => 'backlog']);
        DB::table('canvases')->where('status', 'completed')->update(['status' => 'validated']);
        DB::table('canvases')->where('status', 'discarded')->update(['status' => 'archived']);

        Schema::table('canvases', function (Blueprint $table) {
            $table->string('status', 20)->default('backlog')->change();
        });
    }
};
