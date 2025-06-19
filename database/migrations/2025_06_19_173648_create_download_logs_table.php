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
        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upload_object_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('quickdrop_users')->nullOnDelete();
            $table->string('download_type')->default('single'); // single, bulk
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->bigInteger('bytes_downloaded')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['upload_request_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('download_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_logs');
    }
};
