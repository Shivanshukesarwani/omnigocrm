<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Call extends Model {
 protected $fillable=['subject_type','subject_id','user_id','phone','direction','status','duration_seconds','called_at','recording_path','recording_name','recording_size'];
 protected $casts=['called_at'=>'datetime'];
 public function subject(){return $this->morphTo();}
 public function user(){return $this->belongsTo(User::class);}
}
