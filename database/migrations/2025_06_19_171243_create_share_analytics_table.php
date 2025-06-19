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
        Schema::create('share_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_request_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('unique_views')->default(0);
            $table->unsignedInteger('owner_views')->default(0);
            $table->unsignedInteger('public_views')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('upload_count')->default(0);
            $table->json('device_breakdown')->nullable(); // {desktop: X, mobile: Y, tablet: Z}
            $table->json('browser_breakdown')->nullable(); // {chrome: X, firefox: Y, etc}
            $table->json('country_breakdown')->nullable(); // {US: X, UK: Y, etc}
            $table->json('hourly_views')->nullable(); // Array of 24 hourly view counts
            $table->timestamps();
            
            // Unique constraint and indexes
            $table->unique(['upload_request_id', 'date']);
            $table->index(['upload_request_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_analytics');
    }
};