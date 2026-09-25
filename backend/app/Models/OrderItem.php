<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model {
protected $fillable=['order_id','product_id','description','qty','unit_price','line_total'];
protected $casts=['qty'=>'decimal:2','unit_price'=>'decimal:2','line_total'=>'decimal:2'];
public function product(){return $this->belongsTo(Product::class);}
}