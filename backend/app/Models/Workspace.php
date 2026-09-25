<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Workspace extends Model { protected $fillable=['name','slug','plan','status','trial_ends_at']; protected $casts=['trial_ends_at'=>'datetime']; public function users(){return $this->hasMany(User::class);} public function companies(){return $this->hasMany(Company::class);} }
