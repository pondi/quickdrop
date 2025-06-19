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
        Schema::create('email_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type'); // upload_complete, expiration_warning, download_alert, etc.
            $table->string('recipient_email');
            $table->unsignedBigInteger('quickdrop_user_id')->nullable();
            $table->unsignedBigInteger('upload_request_id')->nullable();
            $table->unsignedBigInteger('upload_object_id')->nullable();
            $table->string('subject');
            $table->json('data')->nullable(); // Additional data for the email
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('quickdrop_user_id');
            $table->index('upload_request_id');
            $table->index('notification_type');
            $table->index('status');
            $table->index(['notification_type', 'created_at']);
            
            $table->foreign('quickdrop_user_id')->references('id')->on('quickdrop_users')->onDelete('cascade');
            $table->foreign('upload_request_id')->references('id')->on('upload_requests')->onDelete('cascade');
            $table->foreign('upload_object_id')->references('id')->on('upload_objects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_notification_logs');
    }
};
