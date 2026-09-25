<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Quotation extends Model{use WorkspaceOwned;protected $fillable=['workspace_id','customer_id','quote_number','status','subtotal','tax','total','valid_until','notes'];protected $casts=['subtotal'=>'decimal:2','tax'=>'decimal:2','total'=>'decimal:2','valid_until'=>'date'];public function customer(){return $this->belongsTo(Customer::class);}public function items(){return $this->hasMany(QuotationItem::class);}}