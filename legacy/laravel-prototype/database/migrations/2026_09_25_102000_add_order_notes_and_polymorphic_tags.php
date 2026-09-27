<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
public function up():void{
if(!Schema::hasColumn('orders','notes'))Schema::table('orders',fn(Blueprint $t)=>$t->text('notes')->nullable());
Schema::create('taggables',function(Blueprint $t){$t->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();$t->morphs('taggable');$t->primary(['tag_id','taggable_id','taggable_type']);});
}
public function down():void{
Schema::dropIfExists('taggables');
if(Schema::hasColumn('orders','notes'))Schema::table('orders',fn(Blueprint $t)=>$t->dropColumn('notes'));
}
};