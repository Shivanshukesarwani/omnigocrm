<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Tag extends Model{
use WorkspaceOwned;
protected $fillable=['workspace_id','name','color'];
public function leads(){return $this->morphedByMany(Lead::class,'taggable');}
public function contacts(){return $this->morphedByMany(Contact::class,'taggable');}
public function customers(){return $this->morphedByMany(Customer::class,'taggable');}
public function taggables(){return $this->morphToMany(Tag::class,'taggable');
}}
