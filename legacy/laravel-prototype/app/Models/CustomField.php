<?php
namespace App\Models;

use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
 use WorkspaceOwned;
 protected $fillable=['workspace_id','entity_type','name','key','type','active'];
 protected $casts=['active'=>'boolean'];
 public function values(){return $this->hasMany(CustomFieldValue::class);}
}
