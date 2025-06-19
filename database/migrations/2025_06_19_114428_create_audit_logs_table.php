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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index(); // login, logout, create, update, delete, download, upload
            $table->string('event_category', 50)->index(); // auth, quickdrop, file, user, system
            $table->text('description');
            $table->string('model_type')->nullable()->index(); // Eloquent model class
            $table->unsignedBigInteger('model_id')->nullable(); // Model ID
            $table->unsignedBigInteger('user_id')->nullable()->index(); // User who performed the action
            $table->string('user_type')->nullable(); // users or quickdrop_users
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('url')->nullable();
            $table->json('old_values')->nullable(); // For update events
            $table->json('new_values')->nullable(); // For create/update events
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['event_type', 'created_at']);
            $table->index(['user_id', 'user_type', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};