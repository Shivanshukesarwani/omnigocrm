<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
 protected $fillable=['workspace_id','customer_id','order_id','amount','method','status','reference','paid_at','notes'];
 protected $casts=['amount'=>'decimal:2','paid_at'=>'datetime'];
 public function customer(){return $this->belongsTo(Customer::class);}
 public function order(){return $this->belongsTo(Order::class);}
}
