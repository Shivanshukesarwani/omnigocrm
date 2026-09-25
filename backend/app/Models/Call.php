<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Call extends Model {
    protected $fillable=['workspace_id','user_id','lead_id','contact_id','customer_id','direction','phone','started_at','ended_at','duration_seconds','recording_path','recording_status'];
    protected $casts=['started_at'=>'datetime','ended_at'=>'datetime'];
}
