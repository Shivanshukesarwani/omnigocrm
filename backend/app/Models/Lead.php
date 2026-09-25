<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Lead extends Model {
    protected $fillable=['first_name','last_name','company','email','mobile','whatsapp','source','status','requirement','notes','assigned_to','created_by','converted_contact_id'];
    public function assignee(): BelongsTo { return $this->belongsTo(User::class,'assigned_to'); }
    public function followUps(): HasMany { return $this->morphMany(FollowUp::class,'subject'); }
}
