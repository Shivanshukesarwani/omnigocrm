<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $fillable = ['name','email','phone','role','password'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['password'=>'hashed']; }
    public function assignedLeads() { return $this->hasMany(Lead::class, 'assigned_to'); }
    public function isAdmin(): bool { return in_array($this->role, ['admin','super_admin'], true); }
}
