<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
 protected $fillable=['workspace_id','customer_id','order_number','status','subtotal','tax','total','ordered_at','notes'];
 protected $casts=['subtotal'=>'decimal:2','tax'=>'decimal:2','total'=>'decimal:2','ordered_at'=>'date'];
 public function customer(){return $this->belongsTo(Customer::class);}
 public function items(){return $this->hasMany(OrderItem::class);}
 public function payments(){return $this->hasMany(Payment::class);}
}
