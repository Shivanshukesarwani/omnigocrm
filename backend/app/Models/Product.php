<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
    protected $fillable=['name','sku','description','price','unit','active'];
    protected $casts=['price'=>'decimal:2','active'=>'boolean'];
}
