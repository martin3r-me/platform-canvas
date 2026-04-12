<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ENUM -> VARCHAR (DB-agnostisch, kein ENUM-Lock-in mehr)
        Schema::table('canvases', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->change();
        });

        // 2. Datenmigration: alte Werte auf neue mappen
        DB::table('canvases')->whereIn('status', ['backlog', 'in_progress', 'review'])->update(['status' => 'open']);
        DB::table('canvases')->where('status', 'validated')->update(['status' => 'completed']);
        DB::table('canvases')->where('status', 'archived')->update(['status' => 'discarded']);
    }

    public function down(): void
    {
        // Daten zurueck mappen
        DB::table('canvases')->where('status', 'open')->update(['status' => 'backlog']);
        DB::table('canvases')->where('status', 'completed')->update(['status' => 'validated']);
        DB::table('canvases')->where('status', 'discarded')->update(['status' => 'archived']);

        // VARCHAR bleibt — kein Zurueck zu ENUM
        Schema::table('canvases', function (Blueprint $table) {
            $table->string('status', 20)->default('backlog')->change();
        });
    }
};
