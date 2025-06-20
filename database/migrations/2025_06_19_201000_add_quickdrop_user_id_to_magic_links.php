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
        Schema::table('magic_links', function (Blueprint $table) {
            $table->foreignId('quickdrop_user_id')
                ->nullable()
                ->after('id')
                ->constrained('quickdrop_users')
                ->cascadeOnDelete();
            
            $table->index('quickdrop_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('magic_links', function (Blueprint $table) {
            $table->dropForeign(['quickdrop_user_id']);
            $table->dropColumn('quickdrop_user_id');
        });
    }
};