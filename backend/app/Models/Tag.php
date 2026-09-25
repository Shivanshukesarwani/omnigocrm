<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Tag extends Model {
 protected $fillable=['workspace_id','name','color'];
 public function leads(){return $this->morphedByMany(Lead::class,'taggable');}
 public function contacts(){return $this->morphedByMany(Contact::class,'taggable');}
}
