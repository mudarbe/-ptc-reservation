<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the ENUM column to include 'cancelled'
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'expired', 'ongoing', 'done', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Revert to original ENUM (without 'cancelled')
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'expired', 'ongoing', 'done') NOT NULL DEFAULT 'pending'");
    }
};