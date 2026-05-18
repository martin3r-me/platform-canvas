<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Setzt last_viewed_at fuer alle bestehenden Canvases auf now(),
 * damit die Staleness-Uhr ab heute fuer alle tickt.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('canvases')
            ->whereNull('last_viewed_at')
            ->update(['last_viewed_at' => now()]);
    }

    public function down(): void
    {
        DB::table('canvases')->update(['last_viewed_at' => null]);
    }
};
