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
        Schema::table('upload_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('upload_requests', 'metadata')) {
                $table->json('metadata')->nullable()->after('last_downloaded_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_requests', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};