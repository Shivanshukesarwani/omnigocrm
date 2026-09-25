<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_for');
            $table->string('type', 40)->default('follow_up');
            $table->string('status', 30)->default('pending')->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('follow_ups'); }
};
