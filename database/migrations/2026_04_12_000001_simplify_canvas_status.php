<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Erst ENUM erweitern, damit neue Werte geschrieben werden koennen
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('backlog','in_progress','review','validated','archived','open','completed','discarded') NOT NULL DEFAULT 'open'");

        // 2. Data migration: map old statuses to new ones
        DB::table('canvases')->whereIn('status', ['backlog', 'in_progress', 'review'])->update(['status' => 'open']);
        DB::table('canvases')->where('status', 'validated')->update(['status' => 'completed']);
        DB::table('canvases')->where('status', 'archived')->update(['status' => 'discarded']);

        // 3. ENUM auf nur noch die neuen Werte einschraenken
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('open','completed','discarded') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        // 1. ENUM erweitern
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('backlog','in_progress','review','validated','archived','open','completed','discarded') NOT NULL DEFAULT 'backlog'");

        // 2. Revert data
        DB::table('canvases')->where('status', 'open')->update(['status' => 'backlog']);
        DB::table('canvases')->where('status', 'completed')->update(['status' => 'validated']);
        DB::table('canvases')->where('status', 'discarded')->update(['status' => 'archived']);

        // 3. ENUM einschraenken
        DB::statement("ALTER TABLE canvases MODIFY COLUMN status ENUM('backlog','in_progress','review','validated','archived') NOT NULL DEFAULT 'backlog'");
    }
};
