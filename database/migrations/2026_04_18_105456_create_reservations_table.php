<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('system_users')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->date('reservation_date');
            $table->string('time_slot'); // e.g., "7-12 pm"
            $table->string('activity_name');
            $table->integer('pax');
            $table->enum('status', ['pending', 'approved', 'declined', 'expired', 'ongoing', 'done'])->default('pending');
            $table->timestamp('hold_expires_at')->nullable(); // For 15-min hold
            $table->text('admin_remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};