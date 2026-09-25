<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone', 30);
            $table->string('direction', 20)->default('outgoing');
            $table->string('status', 30)->default('completed');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->dateTime('called_at');
            $table->string('recording_path')->nullable();
            $table->string('recording_name')->nullable();
            $table->unsignedBigInteger('recording_size')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('calls'); }
};
