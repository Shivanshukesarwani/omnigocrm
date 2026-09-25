<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuotationItem extends Model {
 protected $fillable=['quotation_id','product_id','description','qty','unit_price','line_total'];
 protected $casts=['qty'=>'decimal:2','unit_price'=>'decimal:2','line_total'=>'decimal:2'];
 public function product(){return $this->belongsTo(Product::class);}
 public function quotation(){return $this->belongsTo(Quotation::class);}
}
