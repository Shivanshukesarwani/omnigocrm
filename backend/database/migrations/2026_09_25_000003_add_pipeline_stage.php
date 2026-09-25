<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { if (Schema::hasTable('leads') && !Schema::hasColumn('leads','pipeline_stage')) { Schema::table('leads', function(Blueprint $t){ $t->string('pipeline_stage',40)->default('new')->index(); }); } }
 public function down(): void { if (Schema::hasTable('leads') && Schema::hasColumn('leads','pipeline_stage')) { Schema::table('leads', function(Blueprint $t){ $t->dropColumn('pipeline_stage'); }); } }
};
