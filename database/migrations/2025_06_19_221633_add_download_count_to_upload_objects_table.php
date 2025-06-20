<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('upload_objects', function (Blueprint $table) {
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_objects', function (Blueprint $table) {
            $table->dropColumn(['download_count', 'last_downloaded_at']);
        });
    }
};