<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Product extends Model{use WorkspaceOwned;protected $fillable=['workspace_id','name','sku','description','price','unit','active'];protected $casts=['price'=>'decimal:2','active'=>'boolean'];}