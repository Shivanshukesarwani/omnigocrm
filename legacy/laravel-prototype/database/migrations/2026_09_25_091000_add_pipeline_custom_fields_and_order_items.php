<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  if (Schema::hasTable('leads') && !Schema::hasColumn('leads','pipeline_stage')) {
   Schema::table('leads', function(Blueprint $t){ $t->string('pipeline_stage',40)->default('new')->index(); });
  }
  foreach (['leads','contacts','customers'] as $table) {
   if (Schema::hasTable($table) && !Schema::hasColumn($table,'custom_values')) {
    Schema::table($table, function(Blueprint $t){ $t->json('custom_values')->nullable(); });
   }
  }
  if (Schema::hasTable('orders') && !Schema::hasTable('order_items')) {
   Schema::create('order_items', function(Blueprint $t){
    $t->id(); $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
    $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
    $t->string('description'); $t->decimal('qty',14,2)->default(1); $t->decimal('unit_price',14,2)->default(0); $t->decimal('line_total',14,2)->default(0); $t->timestamps();
   });
  }
 }
 public function down(): void {
  if (Schema::hasTable('order_items')) Schema::dropIfExists('order_items');
  foreach (['leads','contacts','customers'] as $table) {
   if (Schema::hasColumn($table,'custom_values')) Schema::table($table, fn(Blueprint $t)=>$t->dropColumn('custom_values'));
  }
  if (Schema::hasColumn('leads','pipeline_stage')) Schema::table('leads', fn(Blueprint $t)=>$t->dropColumn('pipeline_stage'));
 }
};
