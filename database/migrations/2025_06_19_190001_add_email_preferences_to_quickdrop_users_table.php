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
            $table->boolean('notify_on_upload_complete')->default(true)->after('status');
            $table->boolean('notify_on_download')->default(true)->after('notify_on_upload_complete');
            $table->boolean('notify_on_expiration_warning')->default(true)->after('notify_on_download');
            $table->boolean('notify_marketing')->default(false)->after('notify_on_expiration_warning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quickdrop_users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_on_upload_complete',
                'notify_on_download',
                'notify_on_expiration_warning',
                'notify_marketing'
            ]);
        });
    }
};
