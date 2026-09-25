<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class MessageTemplate extends Model{use WorkspaceOwned;protected $fillable=['workspace_id','name','situation','body','active'];protected $casts=['active'=>'boolean'];}