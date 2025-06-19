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
        Schema::create('quickdrop_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('timezone')->nullable();
            $table->json('preferences')->nullable();
            $table->bigInteger('storage_used')->default(0);
            $table->bigInteger('storage_limit')->default(5368709120); // 5GB default
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('email');
            $table->index('created_at');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quickdrop_users');
    }
};
