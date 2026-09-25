<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller {
 public function showLogin(){return view('auth.login');}
 public function login(Request $request){$data=$request->validate(['email'=>'required|email','password'=>'required|string']);$user=User::where('email',$data['email'])->first();if(!$user||!Hash::check($data['password'],$user->password))return back()->withErrors(['email'=>'Incorrect email or password.'])->withInput();$request->session()->regenerate();$request->session()->put('crm_user_id',$user->id);return redirect()->intended(route('dashboard'));}
 public function logout(Request $request){$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('login');}
}
