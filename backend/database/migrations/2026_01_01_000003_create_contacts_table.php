<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('company')->nullable();
            $table->string('designation')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('mobile', 30)->nullable()->index();
            $table->string('whatsapp', 30)->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('source_lead_id')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('contacts'); }
};
