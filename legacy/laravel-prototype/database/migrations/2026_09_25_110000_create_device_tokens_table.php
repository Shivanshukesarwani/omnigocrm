<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  if(Schema::hasTable('device_tokens')) return;
  Schema::create('device_tokens',function(Blueprint $t){
   $t->id();
   $t->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
   $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
   $t->text('token');
   $t->string('token_hash',64)->unique();
   $t->string('platform',30)->default('android');
   $t->string('device_name')->nullable();
   $t->dateTime('last_used_at')->nullable();
   $t->timestamps();
   $t->index(['workspace_id','user_id']);
  });
 }
 public function down(): void { Schema::dropIfExists('device_tokens'); }
};
