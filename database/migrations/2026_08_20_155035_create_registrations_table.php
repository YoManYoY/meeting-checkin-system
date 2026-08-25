<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('qr_code_id')->constrained('qr_codes')->onDelete('cascade');
            $table->string('name');
            $table->string('lastname');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('organization')->nullable();
            $table->string('position')->nullable();
            $table->enum('registration_type', ['invited', 'walk_in'])->default('walk_in');
            $table->enum('status', ['pending', 'registered', 'replaced'])->default('registered');
            $table->timestamps();

            $table->index(['meeting_id', 'phone']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('registrations');
    }
};
