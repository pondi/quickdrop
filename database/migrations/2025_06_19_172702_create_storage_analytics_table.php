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
        Schema::create('storage_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->bigInteger('total_storage_used')->default(0);
            $table->bigInteger('active_storage_used')->default(0);
            $table->bigInteger('expired_storage_used')->default(0);
            $table->integer('total_files')->default(0);
            $table->integer('active_files')->default(0);
            $table->integer('expired_files')->default(0);
            $table->integer('total_quickdrops')->default(0);
            $table->integer('active_quickdrops')->default(0);
            $table->integer('expired_quickdrops')->default(0);
            $table->json('file_type_breakdown')->nullable();
            $table->json('user_storage_breakdown')->nullable();
            $table->timestamps();
            
            $table->unique('date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_analytics');
    }
};
