<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
 public function index(){
  $workspace=request()->attributes->get('workspace');
  return view('team.index',['users'=>User::where('workspace_id',$workspace->id)->latest()->get()]);
 }
 public function store(Request $r){
  $workspace=$r->attributes->get('workspace');
  $d=$r->validate([
   'name'=>'required|max:120',
   'email'=>['required','email',Rule::unique('users','email')],
   'phone'=>'nullable|max:30',
   'role'=>'required|in:admin,manager,sales',
   'password'=>'required|min:8'
  ]);
  $d['workspace_id']=$workspace->id;
  $user=User::create($d);
  Audit::record($user,'team.member_created','Team member created',['role'=>$user->role]);
  return back()->with('success','Team member added.');
 }
}
