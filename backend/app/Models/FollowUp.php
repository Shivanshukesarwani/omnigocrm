<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FollowUp extends Model
{
    protected $fillable=['subject_type','subject_id','assigned_to','scheduled_for','type','status','note'];
    protected $casts=['scheduled_for'=>'datetime'];
    public function subject(){return $this->morphTo();}
    public function assignee(){return $this->belongsTo(User::class,'assigned_to');}
}
