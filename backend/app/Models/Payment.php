<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model{use WorkspaceOwned;protected $fillable=['workspace_id','customer_id','order_id','reference','amount','method','paid_at','status','notes'];protected $casts=['amount'=>'decimal:2','paid_at'=>'datetime'];public function customer(){return $this->belongsTo(Customer::class);}public function order(){return $this->belongsTo(Order::class);}}