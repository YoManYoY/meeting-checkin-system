<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->string('token', 64)->unique(); // Secure Token for URL
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamps();

            $table->index('token');
        });
    }

    public function down(): void {
        Schema::dropIfExists('qr_codes');
    }
};
