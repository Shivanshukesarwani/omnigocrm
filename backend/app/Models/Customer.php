<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model {
    protected $fillable=['contact_id','customer_code','lifetime_value','status','notes'];
    protected $casts=['lifetime_value'=>'decimal:2'];
    public function contact() { return $this->belongsTo(Contact::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
}
