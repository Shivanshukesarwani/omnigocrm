<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  if (!Schema::hasTable('workspace_subscriptions')) {
   Schema::create('workspace_subscriptions',function(Blueprint $t){
    $t->id();
    $t->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
    $t->string('plan',40)->default('trial');
    $t->string('status',30)->default('trialing');
    $t->dateTime('starts_at')->nullable();
    $t->dateTime('ends_at')->nullable();
    $t->string('provider')->nullable();
    $t->string('external_id')->nullable();
    $t->timestamps();
    $t->index(['workspace_id','status']);
   });
  }

  if (!Schema::hasTable('activity_logs')) {
   Schema::create('activity_logs',function(Blueprint $t){
    $t->id();
    $t->foreignId('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
    $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $t->string('subject_type');
    $t->unsignedBigInteger('subject_id');
    $t->string('action',80);
    $t->text('description')->nullable();
    $t->json('meta')->nullable();
    $t->ipAddress('ip_address')->nullable();
    $t->timestamps();
    $t->index(['subject_type','subject_id']);
    $t->index(['workspace_id','created_at']);
   });
  }

  if (Schema::hasTable('orders')) {
   if (Schema::hasColumn('orders','ordered_at') && !Schema::hasColumn('orders','order_date')) {
    Schema::table('orders',function(Blueprint $t){$t->renameColumn('ordered_at','order_date');});
   } elseif (!Schema::hasColumn('orders','order_date')) {
    Schema::table('orders',function(Blueprint $t){$t->date('order_date')->nullable()->after('total');});
   }
  }
 }

 public function down(): void {
  if (Schema::hasTable('activity_logs')) Schema::dropIfExists('activity_logs');
  if (Schema::hasTable('workspace_subscriptions')) Schema::dropIfExists('workspace_subscriptions');
 }
};
