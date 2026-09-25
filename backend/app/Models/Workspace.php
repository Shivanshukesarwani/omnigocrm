<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model {
    protected $fillable = ['name','slug','plan','status'];
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function leads(): HasMany { return $this->hasMany(Lead::class); }
}
