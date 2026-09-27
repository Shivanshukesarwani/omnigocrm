<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
 public function up():void{
  if(Schema::hasTable('audit_logs'))return;
  Schema::create('audit_logs',function(Blueprint $t){
   $t->id();
   $t->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
   $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
   $t->string('action',80);
   $t->string('entity_type',80)->nullable();
   $t->unsignedBigInteger('entity_id')->nullable();
   $t->json('before')->nullable();
   $t->json('after')->nullable();
   $t->ipAddress('ip')->nullable();
   $t->timestamps();
   $t->index(['workspace_id','action']);
   $t->index(['entity_type','entity_id']);
  });
 }
 public function down():void{Schema::dropIfExists('audit_logs');}
};
