<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('qr_code_id')->constrained('qr_codes')->onDelete('cascade');
            $table->foreignId('registration_id')->constrained('registrations')->onDelete('cascade')->unique(); // Unique ປ้องกัน Check-in ຊ້ຳ
            $table->timestamp('checked_in_at');
            $table->string('signature_path'); // ເກັບ Path ຮູບລາຍເຊັນ
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success'])->default('success');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('check_ins');
    }
};
