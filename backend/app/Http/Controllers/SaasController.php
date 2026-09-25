<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class SaasController extends Controller{
public function showSignup(){return view('auth.signup');}
public function signup(Request $request){
$d=$request->validate(['name'=>'required|max:120','company'=>'required|max:180','email'=>'required|email|max:190|unique:users,email','password'=>'required|min:8|confirmed']);
$user=null;
DB::transaction(function()use($d,&$user){
$slug=Str::slug($d['company']).'-'.Str::lower(Str::random(5));
$w=Workspace::create(['name'=>$d['company'],'slug'=>$slug,'plan'=>'trial','status'=>'active','trial_ends_at'=>now()->addDays(14)]);
WorkspaceSubscription::create(['workspace_id'=>$w->id,'plan'=>'trial','status'=>'trialing','starts_at'=>now(),'ends_at'=>now()->addDays(14)]);
$user=User::create(['name'=>$d['name'],'email'=>$d['email'],'password'=>$d['password'],'role'=>'super_admin','workspace_id'=>$w->id]);
});
$request->session()->regenerate();$request->session()->put('crm_user_id',$user->id);
return redirect()->route('dashboard');
}}