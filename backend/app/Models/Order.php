<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Order extends Model{use WorkspaceOwned;protected $fillable=['workspace_id','customer_id','order_number','status','subtotal','tax','total','order_date'];protected $casts=['subtotal'=>'decimal:2','tax'=>'decimal:2','total'=>'decimal:2','order_date'=>'date'];public function customer(){return $this->belongsTo(Customer::class);}public function items(){return $this->hasMany(OrderItem::class);}public function payments(){return $this->hasMany(Payment::class);}}