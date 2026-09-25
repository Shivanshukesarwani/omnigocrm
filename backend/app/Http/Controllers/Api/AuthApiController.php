<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class AuthApiController extends Controller{
public function login(Request $request){$d=$request->validate(['email'=>'required|email','password'=>'required']);$u=User::with('workspace')->where('email',$d['email'])->first();if(!$u||!Hash::check($d['password'],$u->password))return response()->json(['message'=>'Incorrect credentials'],422);$plain=Str::random(80);ApiToken::create(['user_id'=>$u->id,'token_hash'=>hash('sha256',$plain),'name'=>$request->input('device','Android')]);return ['token'=>$plain,'user'=>$this->safe($u)];}
public function me(Request $request){return ['user'=>$this->safe($request->attributes->get('apiUser'))];}
public function logout(Request $request){$request->attributes->get('apiToken')?->delete();return ['message'=>'Logged out'];}
private function safe(User $u){return ['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'phone'=>$u->phone,'role'=>$u->role,'workspace'=>$u->workspace?['id'=>$u->workspace->id,'name'=>$u->workspace->name,'plan'=>$u->workspace->plan]:null];}
}