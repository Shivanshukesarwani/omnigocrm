<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Lead extends Model{
use WorkspaceOwned;
protected $fillable=['workspace_id','first_name','last_name','company','email','mobile','whatsapp','source','status','pipeline_stage','requirement','notes','assigned_to','created_by','converted_contact_id','company_id'];
public function assignee(){return $this->belongsTo(User::class,'assigned_to');}
public function creator(){return $this->belongsTo(User::class,'created_by');}
public function followUps(){return $this->morphMany(FollowUp::class,'subject');}
public function calls(){return $this->morphMany(Call::class,'subject');}
public function contact(){return $this->belongsTo(Contact::class,'converted_contact_id');}
public function companyModel(){return $this->belongsTo(Company::class,'company_id');}
public function tags(){return $this->morphToMany(Tag::class,'taggable');}
}