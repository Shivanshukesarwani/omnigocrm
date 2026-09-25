<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class AuthApiController extends Controller
{
    public function login(Request $request){
        $data=$request->validate(['email'=>'required|email','password'=>'required|string']);
        $user=User::where('email',$data['email'])->first();
        if(!$user || !Hash::check($data['password'],$user->password)) return response()->json(['message'=>'Incorrect email or password'],422);
        $plain=Str::random(80);
        ApiToken::create(['user_id'=>$user->id,'token_hash'=>hash('sha256',$plain),'name'=>$request->string('device','Android')->toString()]);
        return response()->json(['token'=>$plain,'user'=>$this->safeUser($user)]);
    }
    public function me(Request $request){return response()->json(['user'=>$this->safeUser($request->attributes->get('apiUser'))]);}
    public function logout(Request $request){optional($request->attributes->get('apiToken'))->delete();return response()->json(['message'=>'Logged out']);}
    private function safeUser(User $u){return ['id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'phone'=>$u->phone,'role'=>$u->role];}
}
