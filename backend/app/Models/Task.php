<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Task extends Model { protected $fillable=['workspace_id','assigned_to','title','description','status','priority','due_at','subject_type','subject_id']; protected $casts=['due_at'=>'datetime']; public function subject(){return $this->morphTo();} public function assignee(){return $this->belongsTo(User::class,'assigned_to');} }
