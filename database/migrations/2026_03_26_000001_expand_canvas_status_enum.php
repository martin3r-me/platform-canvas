<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Zuerst Enum erweitern (alle alten + neuen Werte)
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('draft', 'active', 'archived', 'backlog', 'in_progress', 'review', 'validated') NOT NULL DEFAULT 'backlog'");

        // 2. Dann Daten migrieren
        DB::table('canvases')->where('status', 'draft')->update(['status' => 'backlog']);
        DB::table('canvases')->where('status', 'active')->update(['status' => 'in_progress']);

        // 3. Alte Werte aus Enum entfernen
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('backlog', 'in_progress', 'review', 'validated', 'archived') NOT NULL DEFAULT 'backlog'");
    }

    public function down(): void
    {
        // 1. Zuerst Enum erweitern (alle Werte)
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('draft', 'active', 'archived', 'backlog', 'in_progress', 'review', 'validated') NOT NULL DEFAULT 'draft'");

        // 2. Daten zurück-migrieren
        DB::table('canvases')->where('status', 'in_progress')->update(['status' => 'active']);
        DB::table('canvases')->where('status', 'review')->update(['status' => 'active']);
        DB::table('canvases')->where('status', 'validated')->update(['status' => 'active']);
        DB::table('canvases')->where('status', 'backlog')->update(['status' => 'draft']);

        // 3. Neue Werte entfernen
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft'");
    }
};
