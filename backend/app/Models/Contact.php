<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Contact extends Model {
    protected $fillable=['workspace_id','lead_id','company_id','first_name','last_name','phone','email','designation','notes','converted_to_customer_at'];
    protected $casts=['converted_to_customer_at'=>'datetime'];
}
