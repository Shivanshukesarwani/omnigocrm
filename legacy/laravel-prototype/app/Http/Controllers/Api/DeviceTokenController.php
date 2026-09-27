<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
 public function store(Request $r){
  $u=$r->attributes->get('apiUser');
  $d=$r->validate(['token'=>'required|string|max:2000','platform'=>'nullable|in:android','device_name'=>'nullable|max:180']);
  $hash=hash('sha256',$d['token']);
  $device=DeviceToken::withoutGlobalScopes()->updateOrCreate(
   ['token_hash'=>$hash],
   ['workspace_id'=>$u->workspace_id,'user_id'=>$u->id,'token'=>$d['token'],'platform'=>$d['platform']??'android','device_name'=>$d['device_name']??null,'last_used_at'=>now()]
  );
  return response()->json(['id'=>$device->id,'registered'=>true]);
 }

 public function destroy(Request $r,string $id){
  $u=$r->attributes->get('apiUser');
  $device=DeviceToken::withoutGlobalScopes()->where('workspace_id',$u->workspace_id)->where('user_id',$u->id)->findOrFail($id);
  $device->delete();
  return ['deleted'=>true];
 }
}
