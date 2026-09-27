<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Call extends Model{
use WorkspaceOwned;
protected $fillable=['workspace_id','subject_type','subject_id','user_id','phone','direction','status','duration_seconds','called_at','recording_path','recording_name','recording_size'];
protected $casts=['called_at'=>'datetime'];
public function subject(){return $this->morphTo();}
public function user(){return $this->belongsTo(User::class);}
public function durationLabel():string{$s=(int)$this->duration_seconds;return sprintf('%02d:%02d',intdiv($s,60),$s%60);}
}