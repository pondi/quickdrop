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
        Schema::table('quickdrop_users', function (Blueprint $table) {
            $table->boolean('notify_on_all_uploads_complete')->default(true)->after('notify_on_download');
            $table->boolean('notify_on_expiration')->default(true)->after('notify_on_expiration_warning');
            $table->boolean('notify_on_share')->default(true)->after('notify_on_expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quickdrop_users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_on_all_uploads_complete',
                'notify_on_expiration',
                'notify_on_share'
            ]);
        });
    }
};
