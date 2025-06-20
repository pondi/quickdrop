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
            $table->dropForeign(['requesting_user_id']);
            $table->dropColumn('requesting_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_requests', function (Blueprint $table) {
            $table->foreignId('requesting_user_id')->nullable()->after('quickdrop_user_id');
            $table->foreign('requesting_user_id')->references('id')->on('users');
        });
    }
};