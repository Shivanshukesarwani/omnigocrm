<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model {
 protected $fillable=['workspace_id','user_id','action','entity_type','entity_id','before','after','ip'];
 protected $casts=['before'=>'array','after'=>'array'];
 public function user(){return $this->belongsTo(User::class);}
}
