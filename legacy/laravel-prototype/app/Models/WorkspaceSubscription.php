<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkspaceSubscription extends Model{protected $fillable=['workspace_id','plan','status','starts_at','ends_at','provider','external_id'];protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime'];public function workspace(){return $this->belongsTo(Workspace::class);}}