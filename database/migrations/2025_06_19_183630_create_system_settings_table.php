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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general'); // general, storage, email, security
            $table->string('label');
            $table->text('description')->nullable();
            $table->text('validation_rules')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_public')->default(false); // whether it can be exposed to frontend
            $table->timestamps();
            
            $table->index('group');
            $table->index(['group', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
