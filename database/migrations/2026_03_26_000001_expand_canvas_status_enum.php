<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bestehende Daten migrieren
        DB::table('canvases')->where('status', 'draft')->update(['status' => 'backlog']);
        DB::table('canvases')->where('status', 'active')->update(['status' => 'in_progress']);

        // 2. Enum erweitern
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('backlog', 'in_progress', 'review', 'validated', 'archived') NOT NULL DEFAULT 'backlog'");
    }

    public function down(): void
    {
        // Daten zurück-migrieren
        DB::table('canvases')->where('status', 'in_progress')->update(['status' => 'active']);
        DB::table('canvases')->where('status', 'review')->update(['status' => 'active']);
        DB::table('canvases')->where('status', 'validated')->update(['status' => 'active']);
        DB::table('canvases')->where('status', 'backlog')->update(['status' => 'draft']);

        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft'");
    }
};
