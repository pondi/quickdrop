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
        Schema::create('quickdrop_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_request_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->string('country', 2)->nullable(); // ISO country code
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('device_type', 50)->nullable(); // desktop, mobile, tablet
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->boolean('is_owner')->default(false);
            $table->unsignedBigInteger('quickdrop_user_id')->nullable();
            $table->timestamps();
            
            // Indexes for analytics queries
            $table->index(['upload_request_id', 'created_at']);
            $table->index('created_at');
            $table->index('country');
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quickdrop_views');
    }
};