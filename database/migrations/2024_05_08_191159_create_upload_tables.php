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
        Schema::create('upload_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->string('unique_id')->unique();
            $table->bigInteger('file_size');
            $table->string('file_extension');
            $table->string('file_hash');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('original_file_id')->nullable()->constrained('upload_objects')->nullOnDelete();
            $table->string('status')->default('processing');
            $table->boolean('is_encrypted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Add index for faster version lookups
            $table->index(['original_name', 'version']);
        });

        Schema::create('upload_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('comment')->nullable();
            $table->string('reference_number')->nullable();
            $table->foreignId('requesting_user_id')->constrained('users');
            $table->string('unique_request_id', 128)->unique();
            $table->string('verification_token', 128);
            $table->timestamp('expires_at');
            $table->string('status')->default('active');
            $table->boolean('is_encrypted')->default(false);
            $table->string('key_verification_hash')->nullable();
            $table->boolean('allow_public_download')->default(false);
            $table->boolean('allow_public_delete')->default(false);
            $table->boolean('allow_public_upload')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unique_request_id', 'verification_token']);
        });

        Schema::create('upload_request_upload_object', function (Blueprint $table) {
            $table->foreignId('upload_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upload_object_id')->constrained()->cascadeOnDelete();
            $table->primary(['upload_request_id', 'upload_object_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_request_upload_object');
        Schema::dropIfExists('upload_requests');
        Schema::dropIfExists('upload_objects');
    }
};
