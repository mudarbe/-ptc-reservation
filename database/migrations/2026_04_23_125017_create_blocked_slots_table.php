<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('time_slot');
            $table->enum('type', ['maintenance', 'admin_use']);
            $table->foreignId('created_by')->constrained('system_users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Prevent duplicate blocks for same room/date/timeslot
            $table->unique(['room_id', 'date', 'time_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};