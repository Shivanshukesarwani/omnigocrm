<?php
namespace App\Models;
use App\Support\WorkspaceOwned;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model{
use WorkspaceOwned;
protected $fillable=['workspace_id','contact_id','customer_code','lifetime_value','status','notes','company_id','custom_values'];
protected $casts=['lifetime_value'=>'decimal:2','custom_values'=>'array'];
public function contact(){return $this->belongsTo(Contact::class);}
public function followUps(){return $this->morphMany(FollowUp::class,'subject');}
public function calls(){return $this->morphMany(Call::class,'subject');}
public function quotations(){return $this->hasMany(Quotation::class);}
public function orders(){return $this->hasMany(Order::class);}
public function payments(){return $this->hasMany(Payment::class);}
public function tags(){return $this->morphToMany(Tag::class,'taggable');}
 public function activities(){return $this->morphMany(ActivityLog::class,'subject')->latest();}

}