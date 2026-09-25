<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->unique()->constrained('contacts')->cascadeOnDelete();
            $table->string('customer_code')->unique();
            $table->decimal('lifetime_value', 14, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('customers'); }
};
