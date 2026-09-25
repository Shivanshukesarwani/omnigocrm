<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Contact extends Model{
use WorkspaceOwned;
protected $fillable=['workspace_id','first_name','last_name','company','designation','email','mobile','whatsapp','address','notes','source_lead_id','company_id','custom_values'];
protected $casts=['custom_values'=>'array'];
public function customer(){return $this->hasOne(Customer::class);}
public function followUps(){return $this->morphMany(FollowUp::class,'subject');}
public function calls(){return $this->morphMany(Call::class,'subject');}
public function companyModel(){return $this->belongsTo(Company::class,'company_id');}
}