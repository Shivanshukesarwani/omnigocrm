<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class ActivityLog extends Model{use WorkspaceOwned;protected $fillable=['workspace_id','user_id','subject_type','subject_id','action','description','meta','ip_address'];protected $casts=['meta'=>'array'];public function subject(){return $this->morphTo();}public function user(){return $this->belongsTo(User::class);}}