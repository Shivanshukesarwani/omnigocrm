<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model {
    protected $fillable=['workspace_id','contact_id','company_id','customer_code','status','notes'];
}
