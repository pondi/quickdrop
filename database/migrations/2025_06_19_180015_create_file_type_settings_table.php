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
        Schema::create('file_type_settings', function (Blueprint $table) {
            $table->id();
            $table->string('extension', 10)->unique();
            $table->string('mime_type', 100);
            $table->string('display_name', 50);
            $table->string('category', 30); // image, video, audio, document, archive, other
            $table->boolean('is_allowed')->default(true);
            $table->bigInteger('max_size')->nullable(); // max size in bytes for this type
            $table->string('icon_class')->nullable(); // icon class for UI
            $table->integer('priority')->default(0); // for ordering
            $table->json('metadata')->nullable(); // additional settings
            $table->timestamps();
            
            $table->index('is_allowed');
            $table->index('category');
            $table->index(['is_allowed', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_type_settings');
    }
};
