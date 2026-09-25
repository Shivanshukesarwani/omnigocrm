<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Contact extends Model
{
    protected $fillable=['first_name','last_name','company','designation','email','mobile','whatsapp','address','notes','source_lead_id'];
    public function customer() { return $this->hasOne(Customer::class); }
    public function followUps() { return $this->morphMany(FollowUp::class,'subject'); }
    public function calls() { return $this->morphMany(Call::class,'subject'); }
}
