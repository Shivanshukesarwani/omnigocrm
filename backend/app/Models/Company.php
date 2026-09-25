<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;

class Company extends Model {
 use WorkspaceOwned;
 protected $fillable=['workspace_id','name','email','phone','website','address'];
 public function contacts(){return $this->hasMany(Contact::class);}
 public function leads(){return $this->hasMany(Lead::class,'company_id');}
}
