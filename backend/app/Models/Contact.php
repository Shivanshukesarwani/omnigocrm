<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Contact extends Model {
 protected $fillable=['workspace_id','company_id','first_name','last_name','company','designation','email','mobile','whatsapp','address','notes','source_lead_id'];
 public function customer(){return $this->hasOne(Customer::class);}
 public function followUps(){return $this->morphMany(FollowUp::class,'subject');}
 public function calls(){return $this->morphMany(Call::class,'subject');}
 public function companyModel(): BelongsTo{return $this->belongsTo(Company::class,'company_id');}
 public function tags(){return $this->morphToMany(Tag::class,'taggable');}
}
