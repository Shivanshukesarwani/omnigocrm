<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model {
    protected $fillable = ['workspace_id','assigned_to','first_name','last_name','company_name','phone','email','source','status','notes','converted_at'];
    protected $casts = ['converted_at'=>'datetime'];
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}
