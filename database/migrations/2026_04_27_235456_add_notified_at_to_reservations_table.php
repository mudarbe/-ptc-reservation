<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Only add if not already present
            if (!Schema::hasColumn('reservations', 'notified_at')) {
                $table->timestamp('notified_at')->nullable()->after('checked_in_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};