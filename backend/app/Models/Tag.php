<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Tag extends Model{use WorkspaceOwned;protected $fillable=['workspace_id','name','color'];public function leads(){return $this->belongsToMany(Lead::class);}}