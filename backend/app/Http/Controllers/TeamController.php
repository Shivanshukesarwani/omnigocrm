<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
class TeamController extends Controller{
public function index(){return view('team.index',['users'=>User::latest()->get()]);}
public function store(Request $r){$d=$r->validate(['name'=>'required|max:120','email'=>'required|email','phone'=>'nullable|max:30','role'=>'required|in:admin,manager,sales','password'=>'required|min:8']);$d['workspace_id']=$r->attributes->get('workspace')->id;User::create($d);return back()->with('success','Team member added.');}
}