<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable{
use Notifiable;
protected $fillable=['name','email','phone','role','password','workspace_id'];
protected $hidden=['password','remember_token'];
protected function casts():array{return ['password'=>'hashed'];}
public function workspace(){return $this->belongsTo(Workspace::class);}
public function assignedLeads(){return $this->hasMany(Lead::class,'assigned_to');}
public function isAdmin():bool{return in_array($this->role,['admin','super_admin'],true);}
}