<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Task extends Model{
use WorkspaceOwned;
protected $fillable=['workspace_id','assigned_to','subject_type','subject_id','title','description','due_at','priority','status'];
protected $casts=['due_at'=>'datetime'];
public function subject(){return $this->morphTo();}
public function assignee(){return $this->belongsTo(User::class,'assigned_to');}
}