<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canvases', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('status');
            $table->boolean('is_public')->default(false)->after('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('canvases', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'is_public']);
        });
    }
};
