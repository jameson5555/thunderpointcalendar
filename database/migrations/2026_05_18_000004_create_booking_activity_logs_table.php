<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('booking_group');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['booking_group', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_activity_logs');
    }
};