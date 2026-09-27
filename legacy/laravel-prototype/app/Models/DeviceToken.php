<?php
namespace App\Models;

use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
 use WorkspaceOwned;

 protected $fillable=['workspace_id','user_id','token','token_hash','platform','device_name','last_used_at'];
 protected $hidden=['token'];
 protected $casts=['last_used_at'=>'datetime'];

 public function user(){return $this->belongsTo(User::class);}
}
