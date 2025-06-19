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
        // Add quickdrop_user_id to upload_requests table
        Schema::table('upload_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('quickdrop_user_id')->nullable()->after('requesting_user_id');
            $table->index('quickdrop_user_id');
            $table->foreign('quickdrop_user_id')->references('id')->on('quickdrop_users')->onDelete('cascade');
        });

        // Add quickdrop_user_id to upload_objects table
        Schema::table('upload_objects', function (Blueprint $table) {
            $table->unsignedBigInteger('quickdrop_owner_id')->nullable()->after('owner_id');
            $table->index('quickdrop_owner_id');
            $table->foreign('quickdrop_owner_id')->references('id')->on('quickdrop_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_requests', function (Blueprint $table) {
            $table->dropForeign(['quickdrop_user_id']);
            $table->dropIndex(['quickdrop_user_id']);
            $table->dropColumn('quickdrop_user_id');
        });

        Schema::table('upload_objects', function (Blueprint $table) {
            $table->dropForeign(['quickdrop_owner_id']);
            $table->dropIndex(['quickdrop_owner_id']);
            $table->dropColumn('quickdrop_owner_id');
        });
    }
};
