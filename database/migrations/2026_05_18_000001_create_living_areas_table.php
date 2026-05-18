<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('living_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('deep_color', 20);
            $table->string('soft_color', 20);
            $table->text('booking_message')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('living_area_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('living_area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('poobah');
            $table->timestamps();

            $table->unique(['living_area_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('living_area_user');
        Schema::dropIfExists('living_areas');
    }
};