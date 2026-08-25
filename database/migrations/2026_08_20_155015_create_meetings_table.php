<?php
// database/migrations/2026_08_20_155015_create_meetings_table.php
// ไฟล์เต็ม ตัวจริง ห้ามตัด Field อีก

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('meeting_code')->unique(); // MEET-xxxx
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['meeting','seminar','training','other'])->default('meeting');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location'); // ห้องสัมมนา1, 403, ฯลฯ
            $table->enum('status', ['scheduled','ongoing','completed','cancelled'])->default('scheduled');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['created_by_user_id', 'status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('meetings');
    }
};
