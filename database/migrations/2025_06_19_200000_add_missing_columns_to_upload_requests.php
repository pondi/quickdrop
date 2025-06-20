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
            if (!Schema::hasColumn('upload_requests', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            if (!Schema::hasColumn('upload_requests', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('upload_requests', 'max_downloads')) {
                $table->integer('max_downloads')->nullable()->after('reference_number');
            }
            if (!Schema::hasColumn('upload_requests', 'downloads_count')) {
                $table->integer('downloads_count')->default(0)->after('max_downloads');
            }
            if (!Schema::hasColumn('upload_requests', 'last_downloaded_at')) {
                $table->timestamp('last_downloaded_at')->nullable()->after('downloads_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_requests', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deactivated_at', 'max_downloads', 'downloads_count', 'last_downloaded_at']);
        });
    }
};