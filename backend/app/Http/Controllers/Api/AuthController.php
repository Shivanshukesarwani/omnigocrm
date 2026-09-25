<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller {
    public function login(Request $request) {
        $data=$request->validate(['email'=>'required|email','password'=>'required']);
        $user=User::where('email',$data['email'])->first();
        if(!$user || !Hash::check($data['password'],$user->password)) return response()->json(['message'=>'Invalid credentials'],422);
        return ['token'=>$user->createToken('android')->plainTextToken,'user'=>$user];
    }
    public function logout(Request $request) { $request->user()->currentAccessToken()?->delete(); return ['message'=>'Logged out']; }
    public function me(Request $request) { return $request->user(); }
}
