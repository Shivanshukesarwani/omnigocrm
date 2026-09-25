<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomFieldValue extends Model {
 protected $fillable=['custom_field_id','entity_type','entity_id','value'];
 public function field(){return $this->belongsTo(CustomField::class,'custom_field_id');}
 public function entity(){return $this->morphTo();}
}
