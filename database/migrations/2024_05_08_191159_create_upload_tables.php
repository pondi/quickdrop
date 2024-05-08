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
        Schema::create('upload_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->string('unique_id');
            $table->bigInteger('file_size');
            $table->string('file_extension');
            $table->string('file_hash');
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('upload_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requesting_user_id');
            $table->string('unique_request_id')->unique();
            $table->timestamps();

            $table->foreign('requesting_user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('upload_requests_associations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upload_request_id');
            $table->unsignedBigInteger('associated_file_id');

            $table->foreign('upload_request_id')->references('id')->on('upload_requests')->onDelete('cascade');
            $table->foreign('associated_file_id')->references('id')->on('upload_objects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_requests_associations');
        Schema::dropIfExists('upload_requests');
        Schema::dropIfExists('upload_files');
    }
};
