<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Company extends Model { protected $fillable=['workspace_id','name','email','phone','website','address']; public function workspace(){return $this->belongsTo(Workspace::class);} public function contacts(){return $this->hasMany(Contact::class);} public function leads(){return $this->hasMany(Lead::class);} }
