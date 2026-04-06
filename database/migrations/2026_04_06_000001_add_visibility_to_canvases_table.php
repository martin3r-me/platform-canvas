<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canvases', function (Blueprint $table) {
            $table->string('visibility', 10)->default('team')->after('status');
            $table->index(['team_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::table('canvases', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'visibility']);
            $table->dropColumn('visibility');
        });
    }
};
