<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('personal_email');
            $table->string('institutional_email');
            $table->string('role')->default('professor'); // Default role
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_requests');
    }
};