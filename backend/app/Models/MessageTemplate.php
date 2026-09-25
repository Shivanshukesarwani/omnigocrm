<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessageTemplate extends Model {
    protected $fillable=['workspace_id','name','situation','body','is_active'];
    protected $casts=['is_active'=>'boolean'];
}
