<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable {
 protected $fillable=['workspace_id','name','email','phone','role','password'];
 protected $hidden=['password','remember_token'];
 protected function casts(): array { return ['password'=>'hashed']; }
 public function workspace(){return $this->belongsTo(Workspace::class);}
}
