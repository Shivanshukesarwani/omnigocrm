<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Lead extends Model {
 protected $fillable=['workspace_id','company_id','first_name','last_name','company','email','mobile','whatsapp','source','status','requirement','notes','assigned_to','created_by','converted_contact_id'];
 public function assignee(): BelongsTo{return $this->belongsTo(User::class,'assigned_to');}
 public function workspace(): BelongsTo{return $this->belongsTo(Workspace::class);}
 public function companyModel(): BelongsTo{return $this->belongsTo(Company::class,'company_id');}
 public function followUps(){return $this->morphMany(FollowUp::class,'subject');}
 public function calls(){return $this->morphMany(Call::class,'subject');}
 public function tags(){return $this->morphToMany(Tag::class,'taggable');}
}
