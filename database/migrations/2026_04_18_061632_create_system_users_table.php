<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('personal_email');
            $table->string('institutional_email')->unique();
            $table->string('password');
            $table->enum('role', ['professor', 'mis'])->default('professor');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_users');
    }
};